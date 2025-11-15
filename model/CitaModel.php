<?php
require_once __DIR__ . '/../config/conexion.php';

class CitaModel extends BaseModel {
    protected $table = 'citas';
    
    public function generarCodigoCita() {
        return 'CITA-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    }
    
    public function getAllWithFilters($filtro = 'todas', $busqueda = '', $limite = 50, $pagina = 1) {
        $offset = ($pagina - 1) * $limite;
        
        $sql = "SELECT * FROM vista_citas WHERE 1=1";
        $params = [];
        
        if ($filtro !== 'todas') {
            $sql .= " AND estado = ?";
            $params[] = $filtro;
        }
        
        if (!empty($busqueda)) {
            $sql .= " AND (nombre LIKE ? OR correo LIKE ? OR telefono LIKE ? OR codigo_cita LIKE ?)";
            $searchTerm = "%{$busqueda}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        // Contar total
        $stmtCount = $this->db->prepare(str_replace('*', 'COUNT(*) as total', $sql));
        $stmtCount->execute($params);
        $total = $stmtCount->fetch()['total'];
        
        // Obtener citas con paginación
        $sql .= " ORDER BY fecha_solicitud DESC LIMIT ? OFFSET ?";
        $params[] = $limite;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return [
            'citas' => $stmt->fetchAll(),
            'total' => $total,
            'pagina' => $pagina,
            'totalPaginas' => ceil($total / $limite)
        ];
    }
    
    public function updateEstado($id, $estado) {
        $estadosValidos = ['pendiente', 'confirmada', 'completada', 'cancelada'];
        
        if (!in_array($estado, $estadosValidos)) {
            throw new Exception('Estado inválido');
        }
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET estado = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }
    
    public function getStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                SUM(CASE WHEN DATE(fecha_solicitud) = CURDATE() THEN 1 ELSE 0 END) as hoy,
                SUM(CASE WHEN WEEK(fecha_solicitud) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as esta_semana,
                SUM(CASE WHEN MONTH(fecha_solicitud) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as este_mes
            FROM {$this->table}
        ");
        $stats = $stmt->fetch();
        
        // Servicios más solicitados
        $stmt = $this->db->query("
            SELECT servicio, COUNT(*) as total 
            FROM {$this->table} 
            GROUP BY servicio 
            ORDER BY total DESC 
            LIMIT 5
        ");
        $serviciosTop = $stmt->fetchAll();
        
        // Citas recientes
        $stmt = $this->db->query("
            SELECT * FROM vista_citas 
            WHERE estado = 'pendiente' 
            ORDER BY fecha_solicitud DESC 
            LIMIT 10
        ");
        $citasRecientes = $stmt->fetchAll();
        
        return [
            'stats' => $stats,
            'servicios_top' => $serviciosTop,
            'citas_recientes' => $citasRecientes
        ];
    }
}