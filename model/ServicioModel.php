<?php
require_once __DIR__ . '/BaseModel.php';

class ServicioModel extends BaseModel {
    protected $table = 'servicios';
    
    public function getDisponibles() {
        $servicios = $this->getAll(['disponible' => 1], 'orden ASC');
        
        // Decodificar características JSON
        foreach ($servicios as &$servicio) {
            if ($servicio['caracteristicas']) {
                $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
            }
        }
        
        return $servicios;
    }
    
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ?");
        $stmt->execute([$slug]);
        $servicio = $stmt->fetch();
        
        if ($servicio && $servicio['caracteristicas']) {
            $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
        }
        
        return $servicio;
    }
}