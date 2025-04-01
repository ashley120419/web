<?php
session_start();
$conexion = new mysqli("localhost", "root", "", "escuela");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['correo']) || empty($_POST['clave'])) {
        die("❌ Error: Todos los campos son obligatorios.");
    }

    $correo = $_POST['correo'];
    $contrasena = $_POST['clave'];

    $query = "SELECT id FROM usuarios WHERE correo = ? AND contrasena = ? AND tipo = 'padre'";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ss", $correo, $contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $_SESSION['usuario_id'] = $usuario['id'];
    } else {
        die("<p>❌ Error: Correo o contraseña incorrectos.</p>");
    }
}


if (!isset($_SESSION['usuario_id'])) {
    die("<p>❌ Error: Debes iniciar sesión.</p>");
}

$usuario_id = $_SESSION['usuario_id'];
$periodo = isset($_GET['periodo']) ? intval($_GET['periodo']) : 1;


$query_notas = "
    SELECT e.nombre AS estudiante, e.apellido, m.nombre AS materia, n.nota
    FROM notas n
    JOIN estudiantes e ON n.estudiante_id = e.id
    JOIN materias m ON n.materia_id = m.id
    WHERE e.usuario_id = ? AND n.periodo = ?";
$stmt = $conexion->prepare($query_notas);
$stmt->bind_param("ii", $usuario_id, $periodo);
$stmt->execute();
$result_notas = $stmt->get_result();


$query_asistencias = "
    SELECT e.nombre AS estudiante, e.apellido, a.fecha, a.estado
    FROM asistencias a
    JOIN estudiantes e ON a.estudiante_id = e.id
    WHERE e.usuario_id = ? AND a.periodo = ?";
$stmt = $conexion->prepare($query_asistencias);
$stmt->bind_param("ii", $usuario_id, $periodo);
$stmt->execute();
$result_asistencias = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas del Estudiante</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>La clave del éxito es el esfuerzo constante</h2>

    <form method="GET" action="">
        <label for="periodo">Selecciona un período:</label>
        <select name="periodo" id="periodo">
            <option value="1" <?= $periodo == 1 ? "selected" : "" ?>>Período 1</option>
            <option value="2" <?= $periodo == 2 ? "selected" : "" ?>>Período 2</option>
            <option value="3" <?= $periodo == 3 ? "selected" : "" ?>>Período 3</option>
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <h2>Notas del Estudiante - Período <?= $periodo ?></h2>
    <?php if ($result_notas->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th>Estudiante</th>
                <th>Materia</th>
                <th>Calificación</th>
            </tr>
            <?php while ($fila = $result_notas->fetch_assoc()): ?>
                <tr>
                    <td><?= $fila['estudiante'] . " " . $fila['apellido'] ?></td>
                    <td><?= $fila['materia'] ?></td>
                    <td><?= $fila['nota'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>⚠️ No se encontraron notas para este período.</p>
    <?php endif; ?>

    <h2>Asistencias del Estudiante - Período <?= $periodo ?></h2>
    <?php if ($result_asistencias->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th>Estudiante</th>
                <th>Fecha</th>
                <th>Estado</th>
            </tr>
            <?php while ($fila = $result_asistencias->fetch_assoc()): ?>
                <tr>
                    <td><?= $fila['estudiante'] . " " . $fila['apellido'] ?></td>
                    <td><?= $fila['fecha'] ?></td>
                    <td><?= $fila['estado'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>⚠️ No se encontraron asistencias para este período.</p>
    <?php endif; ?>

</body>
</html>

<?php
$conexion->close();
?>
