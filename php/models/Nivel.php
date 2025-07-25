<?php
require_once '../config/database.php';

class Nivel {
    private $conn;
    private $table = 'Nivel';

    public $idNivel;
    public $idLenguaje;
    public $titulo;
    public $ayudaDescripcion;
    public $tiempoLimite;
    public $codigo;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todos los niveles
    public function obtenerTodos() {
        $query = "SELECT n.*, l.nombre as nombreLenguaje, l.foto as fotoLenguaje 
                  FROM " . $this->table . " n 
                  INNER JOIN Lenguaje l ON n.idLenguaje = l.idLenguaje 
                  ORDER BY n.idLenguaje, n.idNivel";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener niveles por lenguaje
    public function obtenerPorLenguaje($idLenguaje) {
        $query = "SELECT n.*, l.nombre as nombreLenguaje, l.foto as fotoLenguaje 
                  FROM " . $this->table . " n 
                  INNER JOIN Lenguaje l ON n.idLenguaje = l.idLenguaje 
                  WHERE n.idLenguaje = :idLenguaje 
                  ORDER BY n.idNivel";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idLenguaje', $idLenguaje);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener nivel por ID
    public function obtenerPorId($id) {
        $query = "SELECT n.*, l.nombre as nombreLenguaje, l.foto as fotoLenguaje 
                  FROM " . $this->table . " n 
                  INNER JOIN Lenguaje l ON n.idLenguaje = l.idLenguaje 
                  WHERE n.idNivel = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Desordenar código para el juego
    public function desordenarCodigo($codigo) {
        // Usar el mismo procesamiento que verificarOrden
        $lineas = array_map('trim', explode("\n", trim($codigo)));
        
        // Filtrar líneas vacías
        $lineas = array_filter($lineas, function($linea) {
            return !empty($linea);
        });
        
        // Reindexar el array
        $lineas = array_values($lineas);
        
        // Desordenar
        shuffle($lineas);
        return $lineas;
    }

    // Verificar si el código está correctamente ordenado
    public function verificarOrden($codigoOriginal, $codigoOrdenado) {
        // Obtener líneas limpias del código original
        $lineasOriginales = $this->obtenerLineasLimpias($codigoOriginal);
        
        // Limpiar las líneas ordenadas con el mismo método
        $lineasOrdenadas = array_map([$this, 'limpiarLinea'], $codigoOrdenado);
        
        // Filtrar líneas vacías de las líneas ordenadas
        $lineasOrdenadas = array_filter($lineasOrdenadas, function($linea) {
            return !empty($linea);
        });
        
        // Reindexar arrays para comparación correcta
        $lineasOrdenadas = array_values($lineasOrdenadas);
        
        // Debug temporal
        error_log("=== DEBUG VERIFICAR ORDEN ===");
        error_log("Líneas originales: " . print_r($lineasOriginales, true));
        error_log("Líneas ordenadas: " . print_r($lineasOrdenadas, true));
        
        // Verificar que tengan la misma cantidad de líneas
        if (count($lineasOriginales) !== count($lineasOrdenadas)) {
            error_log("Diferente cantidad de líneas: " . count($lineasOriginales) . " vs " . count($lineasOrdenadas));
            return false;
        }
        
        // Comparar línea por línea
        for ($i = 0; $i < count($lineasOriginales); $i++) {
            if ($lineasOriginales[$i] !== $lineasOrdenadas[$i]) {
                error_log("Diferencia en línea $i: '{$lineasOriginales[$i]}' vs '{$lineasOrdenadas[$i]}'");
                return false;
            }
        }
        
        error_log("Verificación exitosa!");
        return true;
    }
    
    // Verificar si una línea específica está en la posición correcta
    public function verificarLineaEnPosicion($codigoOriginal, $posicion, $lineaTexto) {
        // Log inicial
        error_log("=== VERIFICAR LÍNEA EN POSICIÓN ===");
        error_log("Posición recibida: " . $posicion);
        error_log("Línea recibida (RAW): " . var_export($lineaTexto, true));
        error_log("Código original completo: " . var_export($codigoOriginal, true));
        
        // Dividir el código original en líneas y procesar igual que verificarOrden
        $lineasOriginales = $this->obtenerLineasLimpias($codigoOriginal);
        
        error_log("Líneas originales procesadas:");
        for ($i = 0; $i < count($lineasOriginales); $i++) {
            error_log("[$i]: " . var_export($lineasOriginales[$i], true));
        }
        
        // Limpiar la línea a verificar con el mismo método
        $lineaLimpia = $this->limpiarLinea($lineaTexto);
        error_log("Línea recibida LIMPIA: " . var_export($lineaLimpia, true));
        
        // Verificar si la posición es válida
        if ($posicion >= count($lineasOriginales) || $posicion < 0) {
            error_log("POSICIÓN INVÁLIDA: $posicion (máximo: " . (count($lineasOriginales) - 1) . ")");
            return false;
        }
        
        // Obtener la línea original sin aplicar limpiarLinea dos veces
        $lineaOriginal = $lineasOriginales[$posicion];
        
        error_log("Comparación:");
        error_log("  Línea original [$posicion]: " . var_export($lineaOriginal, true));
        error_log("  Línea recibida:              " . var_export($lineaLimpia, true));
        error_log("  Longitud original: " . strlen($lineaOriginal));
        error_log("  Longitud recibida: " . strlen($lineaLimpia));
        error_log("  ¿Coinciden? " . ($lineaOriginal === $lineaLimpia ? 'SÍ' : 'NO'));
        
        if ($lineaOriginal !== $lineaLimpia) {
            // Mostrar diferencias byte por byte
            error_log("DIFERENCIAS DETECTADAS:");
            $minLen = min(strlen($lineaOriginal), strlen($lineaLimpia));
            for ($i = 0; $i < $minLen; $i++) {
                if ($lineaOriginal[$i] !== $lineaLimpia[$i]) {
                    error_log("  Pos $i: Original='" . ord($lineaOriginal[$i]) . "' (" . $lineaOriginal[$i] . ") vs Recibida='" . ord($lineaLimpia[$i]) . "' (" . $lineaLimpia[$i] . ")");
                }
            }
            if (strlen($lineaOriginal) !== strlen($lineaLimpia)) {
                error_log("  Longitudes diferentes - Original: " . strlen($lineaOriginal) . " vs Recibida: " . strlen($lineaLimpia));
            }
        }
        
        error_log("============================");
        
        return $lineaOriginal === $lineaLimpia;
        error_log("============================");
        
        return $lineaOriginal === $lineaLimpia;
    }
    
    // Función auxiliar para limpiar líneas de forma consistente
    private function limpiarLinea($linea) {
        error_log("LIMPIAR LÍNEA - Input: " . var_export($linea, true));
        
        // Decodificar entidades HTML si las hay
        $linea = html_entity_decode($linea, ENT_QUOTES, 'UTF-8');
        error_log("LIMPIAR LÍNEA - Después de html_entity_decode: " . var_export($linea, true));
        
        // Limpiar espacios al inicio y final
        $linea = trim($linea);
        error_log("LIMPIAR LÍNEA - Después de trim: " . var_export($linea, true));
        
        // Convertir tabulaciones a espacios (4 espacios por tab)
        $linea = str_replace("\t", "    ", $linea);
        error_log("LIMPIAR LÍNEA - Después de convertir tabs: " . var_export($linea, true));
        
        // Normalizar espacios múltiples a uno solo SOLO al inicio y entre palabras
        // Pero conservar espacios de indentación
        $linea = preg_replace('/[ ]{2,}/', ' ', $linea);
        error_log("LIMPIAR LÍNEA - Output final: " . var_export($linea, true));
        
        return $linea;
    }
    
    // Función auxiliar para obtener líneas limpias (usada por ambas verificaciones)
    public function obtenerLineasLimpias($codigo) {
        // Dividir el código original en líneas
        $lineas = explode("\n", trim($codigo));
        
        // Limpiar cada línea
        $lineasLimpias = array_map([$this, 'limpiarLinea'], $lineas);
        
        // Filtrar líneas vacías
        $lineasLimpias = array_filter($lineasLimpias, function($linea) {
            return !empty($linea);
        });
        
        // Reindexar array
        return array_values($lineasLimpias);
    }
}
?>
