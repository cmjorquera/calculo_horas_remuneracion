<?php
class MySQL
{
    private $conexion;
    private $total_consultas;
    private $error;



    function __construct($bd, $usuario, $pass)
    {
  
        $this->conexion = mysqli_connect('localhost', 'qaseduc_ucomun', 'jorquera86;', "qaseduc_calculo_horario");

        if (!$this->conexion) {
            echo 'Error al conectar a la base de datos: ' . mysqli_connect_error();
            exit;
        }
        // >>> AÑADE ESTO <<<
        // 1) Charset real UTF-8
        if (!mysqli_set_charset($this->conexion, 'utf8mb4')) {
            echo 'Error al establecer charset: ' . mysqli_error($this->conexion);
            exit;
        }
        // 2) (Opcional pero útil) Alinear collation/connection vars
        mysqli_query($this->conexion, "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
        mysqli_query($this->conexion, "SET CHARACTER SET utf8mb4");
        
    }

    public function consulta($consulta)
    {
        $this->total_consultas++;
        $resultado = mysqli_query($this->conexion, $consulta);
        if (!$resultado) {
            echo 'MySQL Error: ' . mysqli_error($this->conexion);
            exit;
        }
        return $resultado;
    }

    public function fetch_array($consulta)
    {
        return mysqli_fetch_array($consulta);
    }


    public function fetch_assoc($consulta)
    {
        return mysqli_fetch_assoc($consulta);
    }

    // Método para obtener el último ID insertado
    public function insert_id()
    {
        return $this->conexion->insert_id;
    }

    public function num_rows($consulta)
    {
        return mysqli_num_rows($consulta);
    }

    public function getTotalConsultas()
    {
        return $this->total_consultas;
    }

    public function guardar($sql)
    {
        $bl = 0;
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            $message = 'Invalid query: ' . mysqli_error($this->conexion) . "\n";
            $message .= mysqli_error($this->conexion);
            $bl = mysqli_errno($this->conexion);
        }
        return $bl;
    }
    // Método adicional en tu clase MySQL
    public function getLastError() {
        return mysqli_error($this->conexion);
    }
    



    // public function borrar($sql)
    // {
    //     $resultado = mysqli_query($sql);
    //     if (!$resultado) {
    //         $message = 'Invalid query: ' . mysqli_error() . "\n";
    //         $message .= mysqli_error();
    //         $bl = mysqli_error();
    //     }
    // }

    public function set_charset($charset)
    {
        if (!mysqli_set_charset($this->conexion, $charset)) {
            echo 'Error al establecer la codificación de caracteres: ' . mysqli_error($this->conexion);
            exit;
        }
    }
    public function prepare($consulta)
    {
        return $this->conexion->prepare($consulta);
    }
    


    // Cierra la conexión no-persistente con el servidor MySQL.
    function CerrarConexion()
    {
        if ($this->conexion) {
            if (!mysqli_close($this->conexion)) {
                $this->error = mysqli_error($this->conexion);
                return false;
            } else {
                return true;
            }
        }
        return false;
    }
    

    function LimpiarConsulta()
    {
        if (!@mysqli_free_result($this->consulta)) {
            $this->error = mysqli_error($this->conexion);
            return (False);
        } else {
            return (True);
        }
    }


    public function escape_string($string)
    {
        return mysqli_real_escape_string($this->conexion, $string);
    }
    // public function getError() {
    //     return $this->mysqli->error;
    // }



    // ******************************************************************************
    // ******************FUNCION PARA ELIMINAR UNIVERSAL*****************************

    function eliminar($bdato, $tabla, $id_equipo) {
        $sql = "DELETE FROM $tabla WHERE id_'$tabla' = $id_equipo";
        return $bdato->consulta($sql);
    }
    
    // ****************************FUNCION PARA ELIMINAR UNIVERSAL*****************
    // *****************************************************************************
    
    
    
    
    public function ultimo_id() {
        return mysqli_insert_id($this->conexion);
    }
    
    
    
    
    
    
    
    
    
    
    

}
