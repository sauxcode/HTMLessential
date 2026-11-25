<?php
// Iniciar sesión para control de acceso
session_start();

// Archivo de base de datos SQLite
$dbFile = 'database.sqlite';

try {
    // Conexión a SQLite (crea el archivo si no existe)
    //$pdo = new PDO('sqlite:' . $dbFile);
	 $pdo = new PDO('sqlite:' . _DIR_ . '/db/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear tablas iniciales si no existen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS respuestas_formulario (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            paso1_nombre TEXT,
            paso1_email TEXT,
            paso2_comentarios TEXT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Crear un usuario por defecto si no existe (Predet.: > Usuario: admin, Contraseña: 123456)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = 'Marlenys'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('Cuevas', PASSWORD_DEFAULT);
        $insertUser = $pdo->prepare("INSERT INTO usuarios (username, password) VALUES ('Marlenys', ?)");
        $insertUser->execute([$hashedPassword]);
    }

} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Manejo de acciones (Logout, Login, Formulario)
$mensaje = "";
$accion = $_GET['action'] ?? 'home';

// Cerrar sesión
if ($accion === 'logout') {
    session_destroy();
    header("Location: ?action=home");
    exit;
}

// Procesar Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$user]);
    $usuarioBD = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioBD && password_verify($pass, $usuarioBD['password'])) {
        $_SESSION['user_id'] = $usuarioBD['id'];
        $_SESSION['username'] = $usuarioBD['username'];
        header("Location: ?action=menu");
        exit;
    } else {
        $mensaje = "Usuario o contraseña incorrectos.";
    }
}

// Procesar Formulario de múltiples páginas (simulado en pasos o guardado final)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_formulario'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ?action=home");
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comentarios = trim($_POST['comentarios'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO respuestas_formulario (user_id, paso1_nombre, paso1_email, paso2_comentarios) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $nombre, $email, $comentarios]);
    $mensaje = "¡Datos del formulario guardados correctamente en la base de datos!";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INF5170 - Proyecto Integrador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
          /* ==========================================
           ESTILOS CSS SEPARADOS (VARIABLES Y RESET)
           ================================---------- */
        :root {
           --primary-color: blue;
           --primary-hover: #ffffff;
            --success-color: #fff;
            --success-hover: #15803d;
            --danger-color: #dc2626;
            --danger-hover: #fff;
            --bg-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #4b5563;
            --border-color: #d1d5db;
        }
        
         [data-theme="dark"] {
            --bg-color: #111827;
            --card-bg: #1f2937;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --border-color: #374151;
            --input-bg: #111827;
            --header-bg: #1e3a8a;
            --footer-bg: #0f172a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;  
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* ==========================================
           COMPONENTES Y DISEÑO (LAYOUT)
           ================================---------- */
        header {
            background-color: var(--primary-color);
            color: #ffffff;
            padding: 1rem 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 50%;
            /*background-color: black; with this looks like a ...*/
            padding: 2px;
        }

        .header-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ffffff;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        main {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .card {
            background-color: var(--card-bg);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            font-weight: bold;
            text-align: center;
            color: var(--text-main);
        }

        h3 {
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
            color: var(--text-muted);
        }

        /* ==========================================
           FORMULARIOS Y CONTROLES
           ================================---------- */
        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus, textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        textarea {
            resize: vertical;
        }

        /* ==========================================
           BOTONES Y ENLACES
           ================================---------- */
        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            color: var(--primary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            color: var(--success-hover);
            margin-bottom: 0.75rem;
        }
        .btn-success:hover {
            background-color: var(--success-hover);
            color: #ffffff;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: #ffffff;
            padding: 0.35rem 0.75rem;
            font-size: 0.875rem;
            width: auto;
        }
        .btn-danger:hover {
            background-color: var(--danger-hover);
            color: var(--danger-color);
        }
        
        .btn-theme {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #ffffff;
            padding: 0.35rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-theme:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .link-back {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .link-back:hover {
            text-decoration: underline;
        }

        /* ==========================================
           UTILIDADES Y MENSAJES
           ================================---------- */
        .alert {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            text-align: center;
            font-size: 0.95rem;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .menu-stack {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .section-divider {
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        footer {
            background-color: var(--primary-color);
            color: #ffffff;
            text-align: center;
            padding: 1rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body id="page-body">
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="brand">
                <img src="R.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                <span class="header-title">.com</span>
            </div>
            <div class="header-actions">
                <!-- Botón Modo Oscuro/Claro -->
            <button onclick="toggleTheme()" class="btn-theme" id="theme-toggle-btn">🌙 Oscuro</button>

            <?php if (isset($_SESSION['username'])): ?>
                    <span class="mr-4">Hola, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <a href="?action=logout" class="btn btn-danger">Cerrar Sesión</a>
            <?php endif; ?>
           </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="card">
            <?php if (!empty($mensaje)): ?>
                <div class="alert">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- VISTA 1: LOGIN (Home) -->
            <?php if (!isset($_SESSION['user_id']) && $accion === 'home'): ?>
                <h2>Bienvenid@ seas</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Usuario:</label>
                        <input type="text" name="username" required  placeholder="admin">
                    </div>
                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" name="password" required placeholder="123456">
                    </div>
                    <button type="submit" name="login" class="btn btn-primary">Entrar</button>
                </form>
                <p class="text-center text-muted">Credenciales por defecto (Pista): algo que llevará al menos, <strong>el resto de su vida</strong></p>

                
                
            <!-- VISTA 2: MENÚ DINÁMICO -->
            <?php elseif (isset($_SESSION['user_id']) && $accion === 'menu'): ?>
                <h2>Panel Principal</h2>
                <p class="text-center text-muted" >¿A dónde desea ir?:</p>
                
                <div class="menu-stack">
                    <a href="?action=formulario" class="btn btn-sucess">📝 Ir al Formulario Multiusuario</a>
                    <p class="text-center text-muted" > o </p>
                    <a href="?action=ejercicios" class="btn btn-primary">📂 Ver sitios disponibles</a>
                </div>
                
                

            <!-- VISTA 3: FORMULARIO MULTI-PÁGINA (Guardado en Base de Datos) -->
           <?php elseif (isset($_SESSION['user_id']) && $accion === 'formulario'): ?>
                <a href="?action=menu" class="link-back">&larr; Volver al Menú</a>
                <h2>Formulario Web</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <h3>Paso 1: Información Personal</h3>
                        <div class="form-group">
                            <label>Nombre Completo:</label>
                            <input type="text" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico:</label>
                            <input type="email" name="email" required>
                        </div>
                    </div>

                    <div class="section-divider">
                        <h3>Paso 2: Detalles del Proyecto</h3>
                        <div class="form-group">
                            <label>Comentarios o Feedback:</label>
                            <textarea name="comentarios" rows="3" placeholder="Escribe aquí..."></textarea>
                        </div>
                    </div>

                    <button type="submit" name="guardar_formulario" class="btn btn-success">Guardar</button>
                </form>

            <!-- VISTA 4: ENLACES A OTROS EJERCICIOS -->
            <?php elseif (isset($_SESSION['user_id']) && $accion === 'ejercicios'): ?>
                <h2>Canales Disponibles</h2>
                <a href="?action=menu" class="link-back">&larr; Volver al Menú</a>
                
                <ul class="list-disc list-inside space-y-2 text-gray-700">
                    <li><a href="https://sauxcode.github.io/BiographyME/aboutME.html" target="_blank" style="color: var(--primary-color);">Sitio 1: Datos personales en la web</a></li>
                    <li><a href="https://sauxcode.github.io/HTMLessential/102labls.html" target="_blank" style="color: var(--primary-color);">Sitio 2: Etiquetas de html</a></li>
                    <li><a href="https://sauxcode.github.io/HTML_overfiles/hellow.html" target="_blank" style="color: var(--primary-color);">Sitio 3: LibertyBank Web</a></li>
                    <li><a href="https://sauxcode.github.io/HTML_overfiles/Index.html" target="_blank" style="color: var(--primary-color);">Sitio 4: SoftWorld.com </a></li>
                    <li><a href="https://github.com/sauxcode/Test-Labs/tree/main" target="_blank" style="color: var(--primary-color);">Sitio 5: Pruebas Páginas y APIs (GitHub-repos)</a></li>
                    <li><a href="https://github.com/sauxcode/LearnHere.pdf" target="_blank" style="color: var(--primary-color);">Sitio 6: Documentos de Aprendizaje e Investigación (GitHub-repos)</a></li>
                    <li><a href="https://formlist-d3s9.onrender.com/" target="_blank" style="color: var(--primary-color);">Sitio 7: Listado de personas (Formulario)</a></li>
                    <li><a href="https://idisvalid.onrender.com" target="_blank" style="color: var(--primary-color);">Sitio 8: Depurador de código de identidad (Cédula)</a></li>
                    <li><a href="https://wasdchat.onrender.com/" target="_blank" style="color: var(--primary-color);">Sitio 9: LeXumen, tu compañero de carrera (Solo en WasdChat.com)</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>  © 2026 sauxcode - Proyecto libre para su modificación, para INF5170 Laboratorio de Programación III (UASD)</p>
    </footer>
    
      <!-- JavaScript para el Modo Oscuro y Memoria Local -->
    <script>
        const htmlElement = document.documentElement;
        const themeBtn = document.getElementById('theme-toggle-btn');

        // Cargar preferencia guardada
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            htmlElement.setAttribute('data-theme', 'dark');
            themeBtn.textContent = '☀️ Claro';
        }

        function toggleTheme() {
            if (htmlElement.getAttribute('data-theme') === 'dark') {
                htmlElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeBtn.textContent = '🌙 Oscuro';
            } else {
                htmlElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeBtn.textContent = '☀️ Claro';
            }
        }
    </script>
</body>
</html>