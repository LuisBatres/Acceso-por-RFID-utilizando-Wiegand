<?php
class Config {
    private $host = "byfcaqpyah9l6i1xrpj2-mysql.services.clever-cloud.com";
    private $usuario = "uedwkrwyweha5rcy";
    private $pass = "PshcRgfZkFIulygInL5Q";
    private $db = "byfcaqpyah9l6i1xrpj2";
    private $conexion;

    public function __construct() {
        $this->connect();
    }

    public function connect() {
        $this->conexion = new mysqli($this->host, $this->usuario, $this->pass, $this->db);
        
        if ($this->conexion->connect_error) {
            die("Conexión fallida: " . $this->conexion->connect_error);
        }
    }

    public function getConexion() {
        return $this->conexion;
    }
}

$config = new Config();
$conexion = $config->getConexion();
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar'])) {
        $nombre = $_POST['nombre'];
        $numero_identificacion = $_POST['numero_identificacion'];

        $sql = "INSERT INTO tarjetas (nombre, numero_identificacion) 
                VALUES (?, ?)";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $nombre, $numero_identificacion);

        if ($stmt->execute()) {
            $mensaje = "Tarjeta agregada correctamente";
        } else {
            $mensaje = "Error al agregar tarjeta: " . $conexion->error;
        }
    } elseif (isset($_POST['eliminar'])) {
        $id = $_POST['id_eliminar'];
        $sql = "DELETE FROM tarjetas WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $mensaje = "Tarjeta eliminada correctamente";
        } else {
            $mensaje = "Error al eliminar tarjeta: " . $conexion->error;
        }
    }
}

$result = $conexion->query("SELECT * FROM tarjetas");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD - Tarjetas de Identificación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        h1, h2 {
            text-align: center;
            color: #333;
        }

        form {
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }

        input[type="text"], input[type="date"], button {
            width: 96.4%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        button:hover {
            background-color: #0056b3;
        }

        table {
            width: 90%;
            max-width: 1000px;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        th, td {
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #e6f7ff;
        }

        .actions {
            text-align: center;
        }

        .actions form {
            display: inline-block;
            width: 79%;
        }

        /* Toast CSS */
        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1;
            right: 30px;
            top: 30px;
            font-size: 17px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.5s, visibility 0.5s, transform 0.5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>

<h1>CRUD - Tarjetas de Identificación</h1>

<?php if ($mensaje != ""): ?>
    <div id="toast"><?php echo $mensaje; ?></div>
    <script>
        window.onload = function() {
            var toast = document.getElementById("toast");
            toast.classList.add("show");
            setTimeout(function(){ 
                toast.classList.remove("show"); 
            }, 3000);
        };
    </script>
<?php endif; ?>

<h2>Agregar nueva tarjeta</h2>
<form method="POST">
    <label for="nombre">Nombre:</label>
    <input type="text" id="nombre" name="nombre" required>
    
    <label for="numero_identificacion">Número de Identificación:</label>
    <input type="text" id="numero_identificacion" name="numero_identificacion" required>
    
    <button type="submit" name="agregar">Agregar Tarjeta</button>
</form>

<h2>Listado de tarjetas</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Número de Identificación</th>
        <th>Fecha de Creación</th>
        <th>Acciones</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['nombre']; ?></td>
            <td><?php echo $row['numero_identificacion']; ?></td>
            <td><?php echo $row['fecha_creacion']; ?></td>
            <td class="actions">
                <form method="POST">
                    <input type="hidden" name="id_eliminar" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="eliminar">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>

<?php
$conexion->close();
?>