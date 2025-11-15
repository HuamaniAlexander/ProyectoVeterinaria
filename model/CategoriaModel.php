<?php
require_once __DIR__ . '/BaseModel.php';

class CategoriaModel extends BaseModel {
    protected $table = 'categorias';
    
    public function getActivas() {
        return $this->getAll(['activo' => 1], 'orden ASC, nombre ASC');
    }
}