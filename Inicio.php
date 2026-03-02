<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

$esAdministrador = ($_SESSION['SISTEMA']['rol'] == 1);
$rolUsuario = $esAdministrador ? 'Administrador' : 'Empleado';

require_once 'Conexion.php';

$stock_minimo = 10;
try {
    $query = "SELECT Titulo, Stock FROM tlibro WHERE Stock <= ? ORDER BY Stock ASC LIMIT 5";
    $stmt = $conexion->prepare($query);
    $stmt->execute([$stock_minimo]);
    $libros_bajo_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al consultar libros con stock bajo: " . $e->getMessage());
    $libros_bajo_stock = [];
}

$tituloPagina = "Inicio";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            padding-top: 80px;
            background-color: #c7ddb5;
            font-family: 'Arial', sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #4b6043 0%, #3a4d34 100%) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            min-height: 80px;
            padding: 0.5rem 1rem !important;
        }
        
        .navbar-container {
            width: 100%;
            padding: 0 15px;
            position: relative;
        }
        
        .navbar-brand-container {
            display: flex;
            align-items: center;
            position: absolute;
            left: 15px;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-family: 'Lora', serif;
            font-size: 1.5rem;
            color: white !important;
            margin: 0;
            padding: 0;
            white-space: nowrap;
        }
        
        .navbar-logo {
            height: 50px;
            margin-right: 10px;
        }
        
        .navbar-content {
            margin-left: 250px;
            padding-left: 20px;
        }
        
        .nav-link {
            font-size: 1.1rem;
            padding: 0.8rem 1.2rem !important;
            color: rgba(255, 255, 255, 0.9) !important;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            transform: translateY(-2px);
            color: white !important;
        }
        
        .nav-link i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }
        
        .user-role {
            font-size: 0.9rem;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 3px 10px;
            margin-left: 5px;
            font-weight: normal;
        }
        
        .administrador-role {
            color: #ffcc00;
        }
        
        .empleado-role {
            color: #4dff4d;
        }
        
        .main-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        .welcome-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: left center;
        }
        
        .content-panel {
            padding: 2rem;
            overflow-y: auto;
        }
        
        .welcome-title {
            text-align: center;
            margin-bottom: 2rem !important;
            position: relative;
            padding-bottom: 15px;
        }
        
        .welcome-title:after {
            content: "";
            display: block;
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, #4b6043, #c7ddb5);
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .book-alert {
            border-left: 5px solid #ffc107;
            border-radius: 5px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .stock-critico {
            color: #dc3545;
            font-weight: bold;
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem !important;
        }
        
        @media (max-width: 992px) {
            .main-container {
                flex-direction: column;
            }
            
            .navbar-brand-container {
                position: relative;
                left: 0;
                padding-left: 0;
            }
            
            .navbar-content {
                margin-left: 0;
                padding-left: 0;
            }
            
            .welcome-image {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="navbar-container">
            <div class="navbar-brand-container">
                <a class="navbar-brand" href="Inicio.php">
                    <img src="./Imagenes/Logo.png" class="navbar-logo" alt="Logo Jardín Literario">
                    <span>Jardín Literario</span>
                </a>
            </div>
            
            <div class="navbar-content">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="PVentaI.php">
                                <i class="fas fa-cash-register"></i> Punto de venta
                            </a>
                        </li>

                        <?php if ($esAdministrador): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="PCompraI.php">
                                    <i class="fas fa-shopping-cart"></i> Compras
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="catalogosDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-database"></i> Registros
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="AutorI.php"><i class="fas fa-user-pen me-2"></i>Autores</a></li>
                                    <li><a class="dropdown-item" href="EditorialI.php"><i class="fas fa-building me-2"></i>Editoriales</a></li>
                                    <li><a class="dropdown-item" href="SagaI.php"><i class="fas fa-layer-group me-2"></i>Sagas</a></li>
                                    <li><a class="dropdown-item" href="GeneroI.php"><i class="fas fa-tags me-2"></i>Géneros</a></li>
                                    <li><a class="dropdown-item" href="LibroI.php"><i class="fas fa-book-open me-2"></i>Libros</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="personasDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-address-book"></i> Personas
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="UsuarioI.php"><i class="fas fa-user-gear me-2"></i>Usuarios</a></li>
                                    <li><a class="dropdown-item" href="ClienteI.php"><i class="fas fa-users me-2"></i>Clientes</a></li>
                                    <li><a class="dropdown-item" href="ProveedorI.php"><i class="fas fa-truck-field me-2"></i>Proveedores</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="LibroConsulta.php">
                                    <i class="fas fa-book-open"></i> Libros
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="detallesDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-list-check"></i> Detalles
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="VentaDetalleI.php"><i class="fas fa-receipt me-2"></i>Ventas</a></li>
                                <li><a class="dropdown-item" href="CompraDetalleI.php"><i class="fas fa-file-invoice-dollar me-2"></i>Compras</a></li>
                            </ul>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <span class="user-role <?= strtolower($rolUsuario) ?>-role">
                                    <?= $rolUsuario ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="dropdown-header text-center">
                                    <small>Usuario</small>
                                    <h6 class="mt-1 mb-0"><?= htmlspecialchars($_SESSION['SISTEMA']['usuario']) ?></h6>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($esAdministrador): ?>
                                <li><a class="dropdown-item" href="LogI.php"><i class="fas fa-history me-2"></i>Registro de actividades</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="CajaI.php"><i class="fas fa-cash-register me-2"></i>Caja</a></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="main-container">
        <div class="col-lg-5 p-0">
            <img src="./Imagenes/JALIT2.jpg" alt="Bienvenida al Jardín Literario" class="welcome-image">
        </div>
        
        <div class="col-lg-7 content-panel">
            <h2 class="welcome-title">
                <i class="fas fa-seedling text-success me-2"></i>Bienvenido, <?= htmlspecialchars($_SESSION['SISTEMA']['usuario']) ?>
            </h2>
            
            <?php if (!empty($libros_bajo_stock)): ?>
            <div class="alert alert-warning book-alert shadow-sm">
                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Libros por agotarse</h4>
                <ul class="mb-0">
                    <?php foreach ($libros_bajo_stock as $libro): ?>
                        <li class="<?= $libro['Stock'] <= 3 ? 'stock-critico' : '' ?>">
                            <?= htmlspecialchars($libro['Titulo']) ?> - 
                            <?php 
                            if ($libro['Stock'] == 1) {
                                echo 'Último ejemplar';
                            } elseif ($libro['Stock'] <= 3) {
                                echo 'Stock crítico: '.$libro['Stock'].' ejemplares';
                            } else {
                                echo 'Stock bajo: '.$libro['Stock'].' ejemplares';
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="alert alert-success book-alert shadow-sm">
                <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Stock adecuado</h4>
                <p class="mb-0">Stock suficiente en todos los libros.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>