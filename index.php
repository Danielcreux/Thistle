<?php
session_start();

// Hardcoded credentials
define('USERNAME', 'danielcreux');
define('PASSWORD', 'danielcreux');

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle login
if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === USERNAME && $_POST['password'] === PASSWORD) {
        $_SESSION['logged_in'] = true;
    } else {
        $login_error = "Usuario o contraseña inválidos.";
    }
}

/**
 * Get the subfolders inside the "imagenes" folder
 *
 * @param string $directory
 * @return array
 */
function getSubfolders($directory) {
    $subfolders = [];
    if (is_dir($directory)) {
        $dirs = scandir($directory);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..') {
                $fullPath = $directory . DIRECTORY_SEPARATOR . $dir;
                if (is_dir($fullPath)) {
                    $subfolders[] = $dir;
                }
            }
        }
    }
    return $subfolders;
}

/**
 * Get image files from a specific subfolder
 *
 * @param string $directory
 * @return array
 */
function getImages($directory) {
    $images = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                // You can add more extensions if needed
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $images[] = $file;
                }
            }
        }
    }
    return $images;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>jocarsa | thistle</title>
    <link rel="icon" type="image/svg+xml" href="https://jocarsa.com/static/logo/thistle.png" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');
        body {
            margin: 0;
            font-family: Ubuntu,Arial, sans-serif;
            background: #f5f5f5;
        }
        header {
            background-color: thistle;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        nav {
            background: thistle; /* Seagreen variant */
            color: white;
            padding: 1rem;
            display: flex;
	flex-direction: row;
	flex-wrap: nowrap;
	justify-content: space-between;
	align-items: center;
	align-content: stretch;
        }
        nav a, nav span {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .login-container {
            max-width: 300px;
            margin: 100px auto;
            background: #ffffff;
            border: 1px solid #ddd;
            padding: 20px;
        }
        .login-container h2 {
            margin-top: 0;
            margin-bottom: 1rem;
            text-align: center;
            color: thistle;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            display: block;
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
        }
        .login-container input[type="submit"] {
            background: thistle;
            color: #fff;
            border: none;
            padding: 0.7rem 1rem;
            cursor: pointer;
            width: 100%;
        }
        .login-container input[type="submit"]:hover {
            background: #276749;
        }
        .login-error {
            color: red;
            margin-bottom: 1rem;
            text-align: center;
        }
        .main-container {
            display: flex;
            min-height: calc(100vh - 124px); /* approximate for header+nav height */
        }
        .left-pane {
            width: 20%;
            background: thistle;
            color:white;
            border-right: 0px solid #ccc;
            padding: 1rem;
            padding-right:0px;
        }
        .left-pane h3 {
            margin-top: 0;
            color: thistle;
        }
        .folder-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .folder-list li {
            margin: 0.5rem 0;
            padding:10px;
            border-bottom:1px solid rgba(255,255,255,0.2);
            color:white;
        }
        .folder-list a {
            color: #333;
            text-decoration: none;
            font-weight: bold;
           color:white;
            width:100%;
        }
        .selected {
            color: thistle;
            background:#f5f5f5;
        }
        .selected a{
        	color:thistle;
        }
        .right-pane {
            width: 80%;
            padding: 1rem;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .grid-container img {
            width: 100%;
            cursor: pointer;
            border: 2px solid #fff;
            transition: border 0.3s ease;
        }
        .grid-container img:hover {
            border: 2px solid thistle;
        }
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            background: #fff;
            border-radius: 4px;
            padding: 1rem;
        }
        .modal-content img {
            max-width: 100%;
            max-height: 80vh;
        }
        .close-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: thistle;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-weight: bold;
        }
        h1{
            display: flex;
	flex-direction: row;
	flex-wrap: nowrap;
	justify-content: center;
	align-items: center;
	align-content: stretch;
margin:0px;
        }
        h1 img{
            width:60px;
        }
         form img{
        	width:100%;
        }
        input{
        	box-sizing:border-box;
        }
    </style>
    <script>
        function openModal(imgSrc) {
            var modalOverlay = document.getElementById('modalOverlay');
            var modalImage = document.getElementById('modalImage');
            modalImage.src = imgSrc;
            modalOverlay.style.display = 'flex';
        }
        function closeModal() {
            var modalOverlay = document.getElementById('modalOverlay');
            var modalImage = document.getElementById('modalImage');
            modalImage.src = '';
            modalOverlay.style.display = 'none';
        }
       
    </script>
</head>
<body>

<?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
    <!-- LOGIN FORM -->
    <div class="login-container">
        <h2>Danielcreux | thistle</h2>
        <?php if (isset($login_error)): ?>
            <div class="login-error"><?php echo $login_error; ?></div>
        <?php endif; ?>
        <form method="post" action="">
        <img src="https://jocarsa.com/static/logo/thistle.png">
            <input type="text" name="username" placeholder="Usuario" required />
            <input type="password" name="password" placeholder="Contraseña" required />
            <input type="submit" value="Entrar" />
        </form>
    </div>
<?php else: ?>
    <!-- HEADER -->
   

    <!-- NAVIGATION -->
    <nav>
    <h1>
                <img src="https://jocarsa.com/static/logo/thistle.png" alt="Logo">Danielcreux | thistle
            </h1>
        <span>Usuario: <?php echo USERNAME; ?></span>
        <a href="?action=logout">Cerrar Sesión</a>
    </nav>

    <!-- MAIN CONTENT: TWO-PANE LAYOUT -->
    <div class="main-container">
        <!-- LEFT PANE: List of subfolders -->
        <div class="left-pane">
            <h3>VirtualHosts</h3>
            <ul class="folder-list">
                <?php
                $subfolders = getSubfolders('imagenes');
                $currentSubfolder = isset($_GET['subfolder']) ? $_GET['subfolder'] : '';
                foreach ($subfolders as $sf):
                    $selectedClass = ($sf === $currentSubfolder) ? 'selected' : '';
                    // Build the URL with the subfolder param
                    $url = $_SERVER['PHP_SELF'] . '?subfolder=' . urlencode($sf);
                    echo "<li class=\"$selectedClass\" ><a href=\"$url\">$sf</a></li>";
                endforeach;
                ?>
            </ul>
        </div>
        
        <!-- RIGHT PANE: Images -->
        <div class="right-pane">
            <?php if ($currentSubfolder): ?>
                <?php
                $images = getImages('imagenes' . DIRECTORY_SEPARATOR . $currentSubfolder);
                if (count($images) > 0):
                ?>
                    <div class="grid-container">
                        <?php foreach ($images as $img): ?>
                            <?php $imgPath = 'imagenes/' . $currentSubfolder . '/' . $img; ?>
                            <img 
                                src="<?php echo $imgPath; ?>" 
                                alt="<?php echo $img; ?>" 
                                onclick="openModal('<?php echo $imgPath; ?>')" />
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No se encontraron imágenes en la carpeta <strong><?php echo htmlspecialchars($currentSubfolder); ?></strong>.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>Por favor, selecciona un VirtualHost de la lista para ver sus imágenes.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL OVERLAY -->
    <div class="modal-overlay" id="modalOverlay" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="close-btn" onclick="closeModal()">×</button>
            <img id="modalImage" src="" alt="Imagen grande" />
        </div>
    </div>

<?php endif; ?>

</body>
</html>
