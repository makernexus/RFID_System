<?php

// Search Client Info Database
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['manager', 'admin']);  // Require manager or admin role
include 'commonfunctions.php';
require_once 'admin_log_functions.php';

allowWebAccess();  // if IP not allowed, then die

// Handle photo upload
if (isset($_POST['action']) && $_POST['action'] === 'uploadPhoto') {
    header('Content-Type: application/json');
    
    $clientID = $_POST['clientID'];
    
    if (!isset($_FILES['photoFile']) || $_FILES['photoFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit();
    }
    
    $file = $_FILES['photoFile'];
    $filename = strtolower($file['name']);
    
    // Check file extension (must be .jpg lowercase)
    if (!preg_match('/\.jpg$/', $filename)) {
        echo json_encode(['success' => false, 'message' => 'Only .jpg files (lowercase extension) are allowed']);
        exit();
    }
    
    // Get image dimensions
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid image file']);
        exit();
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    // Check if approximately square (within 20% tolerance)
    $aspectRatio = $width / $height;
    if ($aspectRatio < 0.8 || $aspectRatio > 1.2) {
        echo json_encode(['success' => false, 'message' => 'Image must be approximately square. Current aspect ratio: ' . round($aspectRatio, 2)]);
        exit();
    }
    
    // Load the image
    $sourceImage = imagecreatefromjpeg($file['tmp_name']);
    if ($sourceImage === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to load JPEG image']);
        exit();
    }
    
    // Check file size and resize if needed
    $fileSize = $file['size'];
    $targetSize = 200 * 1024; // 200KB
    
    if ($fileSize > $targetSize) {
        // Calculate new dimensions to reduce file size
        $scaleFactor = sqrt($targetSize / $fileSize);
        $newWidth = (int)($width * $scaleFactor);
        $newHeight = (int)($height * $scaleFactor);
        
        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save to destination
        $destinationPath = "photo/" . $clientID . ".jpg";
        $saved = imagejpeg($resizedImage, $destinationPath, 85);
        
        imagedestroy($resizedImage);
        imagedestroy($sourceImage);
    } else {
        // Save original image
        $destinationPath = "photo/" . $clientID . ".jpg";
        $saved = move_uploaded_file($file['tmp_name'], $destinationPath);
        imagedestroy($sourceImage);
    }
    
    if ($saved) {
        // Log the photo upload
        $ini_array = parse_ini_file("rfidconfig.ini", true);
        $dbUser = $ini_array["SQL_DB"]["writeUser"];
        $dbPassword = $ini_array["SQL_DB"]["writePassword"];
        $dbName = $ini_array["SQL_DB"]["dataBaseName"];
        
        $logCon = mysqli_connect("localhost", $dbUser, $dbPassword, $dbName);
        if ($logCon) {
            logAdminAction($logCon, 'photo_upload', $clientID, 'photo', 
                null, null, 'Photo uploaded via client search');
            mysqli_close($logCon);
        }
        
        echo json_encode(['success' => true, 'message' => 'Photo uploaded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save image file']);
    }
    exit();
}

// Get search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$results = [];
$resultCount = 0;

if ($searchQuery !== '') {
    // Perform search
    $ini_array = parse_ini_file("rfidconfig.ini", true);
    $dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
    $dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
    $dbName = $ini_array["SQL_DB"]["dataBaseName"];
    
    $con = mysqli_connect("localhost", $dbUser, $dbPassword, $dbName);
    
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }
    
    // Use direct query with escaped parameter
    $escapedSearch = mysqli_real_escape_string($con, $searchQuery);
    $searchSQL = "SELECT clientID, firstName, lastName, displayClasses, MOD_Eligible, dateLastSeen 
                  FROM clientInfo 
                  WHERE lastName LIKE '%" . $escapedSearch . "%' 
                  ORDER BY lastName, firstName 
                  LIMIT 100";
    
    $result = mysqli_query($con, $searchSQL);
    
    if (!$result) {
        echo "Query error: " . mysqli_error($con);
    } else {
        while($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
        $resultCount = count($results);
    }
    
    mysqli_close($con);
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Client Search</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="style.css" rel="stylesheet">
  
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 0;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .page-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      margin: -20px -20px 30px -20px;
      border-radius: 0 0 10px 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .page-header h1 {
      margin: 0;
      font-size: 28px;
      font-weight: 600;
    }
    
    .search-container {
      background: white;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    
    .search-input {
      padding: 12px 15px;
      width: 100%;
      max-width: 400px;
      border: 2px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
      transition: border-color 0.2s;
    }
    
    .search-input:focus {
      outline: none;
      border-color: #667eea;
    }
    
    .search-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      margin-left: 10px;
      transition: transform 0.2s;
    }
    
    .search-btn:hover {
      transform: translateY(-2px);
    }
    
    .results-header {
      background: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    
    .results-header h2 {
      margin: 0;
      color: #333;
      font-size: 22px;
    }
    
    .client-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 20px;
    }
    
    .client-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
      display: flex;
      gap: 20px;
    }
    
    .client-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .client-photo {
      flex-shrink: 0;
    }
    
    .client-photo img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 8px;
      border: 3px solid #f0f0f0;
    }
    
    .client-info {
      flex-grow: 1;
    }
    
    .client-name {
      font-size: 20px;
      font-weight: 600;
      color: #333;
      margin: 0 0 10px 0;
    }
    
    .client-detail {
      margin: 5px 0;
      color: #666;
      font-size: 14px;
    }
    
    .client-detail strong {
      color: #333;
      display: inline-block;
      min-width: 100px;
    }
    
    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      margin-right: 5px;
      margin-top: 5px;
    }
    
    .badge-mod {
      background-color: #4caf50;
      color: white;
    }
    
    .badge-class {
      background-color: #2196f3;
      color: white;
    }
    
    .no-results {
      background: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
      color: #666;
    }
    
    .edit-photo-btn {
      background-color: #667eea;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      margin-top: 10px;
      transition: background-color 0.2s;
    }
    
    .edit-photo-btn:hover {
      background-color: #5568d3;
    }
    
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 30px;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    
    .modal-header {
      margin-bottom: 20px;
      border-bottom: 2px solid #f0f0f0;
      padding-bottom: 15px;
    }
    
    .modal-header h2 {
      margin: 0;
      color: #333;
      font-size: 24px;
    }
    
    .modal-body {
      margin-bottom: 20px;
    }
    
    .file-input-container {
      margin: 20px 0;
    }
    
    .file-input-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }
    
    .file-input {
      width: 100%;
      padding: 10px;
      border: 2px dashed #ddd;
      border-radius: 5px;
      cursor: pointer;
    }
    
    .modal-footer {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      padding-top: 15px;
      border-top: 2px solid #f0f0f0;
    }
    
    .modal-btn {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      transition: opacity 0.2s;
    }
    
    .modal-btn:hover {
      opacity: 0.9;
    }
    
    .modal-btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .modal-btn-secondary {
      background-color: #e0e0e0;
      color: #333;
    }
    
    .modal-message {
      padding: 12px;
      border-radius: 5px;
      margin-bottom: 15px;
      font-size: 14px;
    }
    
    .modal-message.success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .modal-message.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .modal-info {
      background-color: #e3f2fd;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 15px;
      font-size: 13px;
      color: #0d47a1;
    }
    
    .modal-info ul {
      margin: 5px 0 0 20px;
      padding: 0;
    }
    
    .modal-info li {
      margin: 3px 0;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 10px;
      }
      
      .page-header {
        margin: -10px -10px 20px -10px;
        padding: 20px;
      }
      
      .page-header h1 {
        font-size: 22px;
      }
      
      .search-container {
        padding: 15px;
      }
      
      .search-input {
        width: 100%;
        max-width: 100%;
        margin-bottom: 10px;
      }
      
      .search-btn {
        width: 100%;
        margin-left: 0;
      }
      
      .client-grid {
        grid-template-columns: 1fr;
      }
      
      .client-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }
      
      .client-detail strong {
        display: block;
        margin-bottom: 2px;
      }
    }
  </style>
</head>

<body>
    <?php 
    ob_start();
    include 'auth_header.php';
    echo ob_get_clean();
    ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Client Search</h1>
        </div>
        
        <div class="search-container">
            <form method="GET" action="">
                <label for="search" style="display: block; margin-bottom: 10px; font-weight: 600; color: #333;">
                    Search by Last Name:
                </label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       class="search-input" 
                       placeholder="Enter last name (partial match)" 
                       value="<?php echo htmlspecialchars($searchQuery); ?>"
                       autofocus>
                <button type="submit" class="search-btn">Search</button>
                <?php if ($searchQuery !== ''): ?>
                    <a href="rfidclientsearch.php" style="margin-left: 10px; color: #666; text-decoration: none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if ($searchQuery !== ''): ?>
            <?php if ($resultCount > 0): ?>
                <div class="results-header">
                    <h2><?php echo $resultCount; ?> Result<?php echo $resultCount != 1 ? 's' : ''; ?> Found</h2>
                </div>
                
                <div class="client-grid">
                    <?php foreach ($results as $client): ?>
                        <div class="client-card">
                            <div class="client-photo">
                                <img src="photo/<?php echo htmlspecialchars($client['clientID']); ?>.jpg" 
                                     alt="Photo of <?php echo htmlspecialchars($client['firstName'] . ' ' . $client['lastName']); ?>"
                                     onerror="this.src='WeNeedAPhoto.png'">
                            </div>
                            <div class="client-info">
                                <h3 class="client-name">
                                    <?php echo htmlspecialchars($client['lastName'] . ', ' . $client['firstName']); ?>
                                </h3>
                                <div class="client-detail">
                                    <strong>Client ID:</strong> <?php echo htmlspecialchars($client['clientID']); ?>
                                </div>
                                <?php if (!empty($client['dateLastSeen'])): ?>
                                    <div class="client-detail">
                                        <strong>Last Seen:</strong> <?php echo htmlspecialchars($client['dateLastSeen']); ?>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top: 10px;">
                                    <?php if ($client['MOD_Eligible'] == 1): ?>
                                        <span class="badge badge-mod">MOD</span>
                                    <?php endif; ?>
                                    <?php if (!empty(trim($client['displayClasses']))): ?>
                                        <span class="badge badge-class"><?php echo htmlspecialchars($client['displayClasses']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <button class="edit-photo-btn" 
                                        onclick="openPhotoModal('<?php echo htmlspecialchars($client['clientID']); ?>', '<?php echo htmlspecialchars($client['firstName'] . ' ' . $client['lastName']); ?>')">
                                    Edit Photo
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p style="font-size: 18px; margin: 0;">
                        No results found for "<?php echo htmlspecialchars($searchQuery); ?>"
                    </p>
                    <p style="margin-top: 10px; color: #999;">
                        Try a different search term
                    </p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <p style="font-size: 18px; margin: 0;">
                    Enter a last name to search
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Photo Upload Modal -->
    <div id="photoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Photo</h2>
            </div>
            <div class="modal-body">
                <div id="modalMessage"></div>
                <div class="modal-info">
                    <strong>Requirements:</strong>
                    <ul>
                        <li>File must be in .jpg format (lowercase extension)</li>
                        <li>Image must be approximately square</li>
                        <li>Files larger than 200KB will be automatically resized</li>
                    </ul>
                </div>
                <p id="modalClientName" style="font-weight: 600; color: #333; margin-bottom: 15px;"></p>
                <form id="photoUploadForm" enctype="multipart/form-data">
                    <input type="hidden" id="modalClientID" name="clientID">
                    <input type="hidden" name="action" value="uploadPhoto">
                    <div class="file-input-container">
                        <label for="photoFile" class="file-input-label">Select Photo:</label>
                        <input type="file" 
                               id="photoFile" 
                               name="photoFile" 
                               class="file-input" 
                               accept=".jpg"
                               required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closePhotoModal()">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" onclick="uploadPhoto()">Upload</button>
            </div>
        </div>
    </div>
    
    <script>
        function openPhotoModal(clientID, clientName) {
            document.getElementById('modalClientID').value = clientID;
            document.getElementById('modalClientName').textContent = 'Client: ' + clientName;
            document.getElementById('modalMessage').innerHTML = '';
            document.getElementById('photoUploadForm').reset();
            document.getElementById('photoModal').style.display = 'block';
        }
        
        function closePhotoModal() {
            document.getElementById('photoModal').style.display = 'none';
        }
        
        function uploadPhoto() {
            const form = document.getElementById('photoUploadForm');
            const fileInput = document.getElementById('photoFile');
            const messageDiv = document.getElementById('modalMessage');
            
            // Validate file is selected
            if (!fileInput.files || fileInput.files.length === 0) {
                messageDiv.innerHTML = '<div class="modal-message error">Please select a file</div>';
                return;
            }
            
            const file = fileInput.files[0];
            
            // Validate .jpg extension (lowercase)
            if (!file.name.toLowerCase().endsWith('.jpg')) {
                messageDiv.innerHTML = '<div class="modal-message error">File must have .jpg extension</div>';
                return;
            }
            
            // Show uploading message
            messageDiv.innerHTML = '<div class="modal-message">Uploading...</div>';
            
            // Create FormData and send via AJAX
            const formData = new FormData(form);
            
            fetch('rfidclientsearch.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="modal-message success">' + data.message + '</div>';
                    // Reload the page after a short delay to show the new photo
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    messageDiv.innerHTML = '<div class="modal-message error">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="modal-message error">Upload failed: ' + error.message + '</div>';
            });
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('photoModal');
            if (event.target == modal) {
                closePhotoModal();
            }
        }
    </script>
</body>

</html>
