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
  <!-- Cropper.js CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
  
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
    
    /* Crop Modal Styles */
    #cropModal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
    }
    
    .crop-modal-content {
      background-color: #fff;
      margin: 2% auto;
      padding: 0;
      border-radius: 8px;
      width: 90%;
      max-width: 800px;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    
    .crop-modal-header {
      padding: 20px;
      border-bottom: 1px solid #e0e0e0;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 8px 8px 0 0;
    }
    
    .crop-modal-header h2 {
      margin: 0;
      font-size: 20px;
    }
    
    .crop-modal-body {
      padding: 20px;
      overflow: auto;
      flex: 1;
      display: flex;
      gap: 20px;
    }
    
    .crop-controls-panel {
      width: 25%;
      min-width: 200px;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    
    .crop-container {
      flex: 1;
      max-height: 500px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    #cropImage {
      max-width: 100%;
      max-height: 500px;
      display: block;
    }
    
    .crop-info {
      background-color: #f0f8ff;
      border-left: 4px solid #667eea;
      padding: 15px;
      border-radius: 4px;
    }
    
    .crop-info p {
      margin: 5px 0;
      color: #333;
      font-size: 13px;
    }
    
    .image-adjustments {
      background-color: #f8f9fa;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      padding: 15px;
    }
    
    .image-adjustments h4 {
      margin: 0 0 12px 0;
      font-size: 14px;
      color: #333;
      font-weight: 600;
    }
    
    .adjustment-control {
      margin-bottom: 12px;
    }
    
    .adjustment-control:last-child {
      margin-bottom: 0;
    }
    
    .adjustment-control label {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
      font-size: 13px;
      color: #555;
      font-weight: 500;
    }
    
    .adjustment-control input[type="range"] {
      width: 100%;
      height: 6px;
      background: #ddd;
      border-radius: 3px;
      outline: none;
      -webkit-appearance: none;
    }
    
    .adjustment-control input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 16px;
      height: 16px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      cursor: pointer;
      border-radius: 50%;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .adjustment-control input[type="range"]::-moz-range-thumb {
      width: 16px;
      height: 18px 12px;
      border-radius: 4px;
      font-size: 12px;
      cursor: pointer;
      width: 100%;
      margin-top: 8px;
    }
    
    .reset-btn:hover {
      background: #5a6268;
    }
    
    .auto-touchup-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 10px 12px;
      border-radius: 4px;
      font-size: 13px;
      cursor: pointer;
      width: 100%;
      font-weight: 500;
    }
    
    .auto-touchup-btn:hover {
      opacity: 0.9;
    }
    
    .crop-modal-footer {
      padding: 15px 20px;
      border-top: 1px solid #e0e0e0;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
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
      
      .crop-modal-body {
        flex-direction: column;
      }
      
      .crop-controls-panel {
        width: 100%;
        min-width: auto;
      }
      
      .crop-container {
        max-height: 300px;
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
                        <li>You'll be able to crop and adjust the photo before uploading</li>
                        <li>Optional auto touchup feature available</li>
                        <li>Final image will be automatically optimized</li>
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
    
    <!-- Crop Modal -->
    <div id="cropModal" class="modal">
        <div class="crop-modal-content">
            <div class="crop-modal-header">
                <h2>Crop Photo to Square</h2>
            </div>
            <div class="crop-modal-body">
                <div class="crop-controls-panel">
                    <div class="crop-info">
                        <p><strong>Instructions:</strong> Drag to adjust the crop area.</p>
                        <p id="cropReason"></p>
                    </div>
                    <button type="button" class="auto-touchup-btn" onclick="autoTouchup()">✨ Auto Touchup</button>
                    <div class="image-adjustments">
                        <h4>Manual Adjustments</h4>
                        <div class="adjustment-control">
                            <label>
                                <span>Brightness</span>
                                <span id="brightnessValue">0</span>
                            </label>
                            <input type="range" id="brightnessSlider" min="-50" max="50" value="0" step="1">
                        </div>
                        <div class="adjustment-control">
                            <label>
                                <span>Contrast</span>
                                <span id="contrastValue">0</span>
                            </label>
                            <input type="range" id="contrastSlider" min="-50" max="50" value="0" step="1">
                        </div>
                        <div class="adjustment-control">
                            <label>
                                <span>Shadows</span>
                                <span id="shadowsValue">0</span>
                            </label>
                            <input type="range" id="shadowsSlider" min="-50" max="50" value="0" step="1">
                        </div>
                        <div class="adjustment-control">
                            <label>
                                <span>Sharpening</span>
                                <span id="sharpeningValue">0</span>
                            </label>
                            <input type="range" id="sharpeningSlider" min="0" max="100" value="0" step="1">
                        </div>
                        <button type="button" class="reset-btn" onclick="resetAdjustments()">Reset All</button>
                    </div>
                </div>
                <div class="crop-container">
                    <img id="cropImage" src="" alt="Image to crop">
                </div>
            </div>
            <div class="crop-modal-footer">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeCropModal()">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" onclick="applyCrop()">Apply Crop & Upload</button>
            </div>
        </div>
    </div>
    
    <!-- Cropper.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    
    <script>
        let cropper = null;
        let currentFile = null;
        let touchupApplied = false;
        let originalImageData = null;
        let pristineImageData = null;  // Store truly original image before any touchup
        let adjustments = {
            brightness: 0,
            contrast: 0,
            shadows: 0,
            sharpening: 0
        };
        let adjustmentTimeout = null;  // For debouncing slider changes
        let isApplyingAdjustments = false;  // Prevent overlapping adjustments
        
        // Add event listener to file input
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('photoFile');
            if (fileInput) {
                fileInput.addEventListener('change', handleFileSelect);
            }
            
            // Add event listeners to adjustment sliders
            document.getElementById('brightnessSlider').addEventListener('input', function(e) {
                adjustments.brightness = parseInt(e.target.value);
                document.getElementById('brightnessValue').textContent = e.target.value;
                debouncedApplyAdjustments();
            });
            
            document.getElementById('contrastSlider').addEventListener('input', function(e) {
                adjustments.contrast = parseInt(e.target.value);
                document.getElementById('contrastValue').textContent = e.target.value;
                debouncedApplyAdjustments();
            });
            
            document.getElementById('shadowsSlider').addEventListener('input', function(e) {
                adjustments.shadows = parseInt(e.target.value);
                document.getElementById('shadowsValue').textContent = e.target.value;
                debouncedApplyAdjustments();
            });
            
            document.getElementById('sharpeningSlider').addEventListener('input', function(e) {
                adjustments.sharpening = parseInt(e.target.value);
                document.getElementById('sharpeningValue').textContent = e.target.value;
                debouncedApplyAdjustments();
            });
        });
        
        function debouncedApplyAdjustments() {
            // Clear any pending adjustment
            if (adjustmentTimeout) {
                clearTimeout(adjustmentTimeout);
            }
            
            // Wait 150ms after the last slider change before applying
            adjustmentTimeout = setTimeout(function() {
                applyAdjustments();
            }, 150);
        }
        
        function handleFileSelect(event) {
            const file = event.target.files[0];
            const messageDiv = document.getElementById('modalMessage');
            
            if (!file) return;
            
            // Validate .jpg extension (lowercase)
            if (!file.name.toLowerCase().endsWith('.jpg')) {
                messageDiv.innerHTML = '<div class="modal-message error">File must have .jpg extension</div>';
                event.target.value = '';
                return;
            }
            
            currentFile = file;
            
            // Always show crop modal for all uploads
            showCropModal(file, 'Adjust the crop area to select the square portion you want to use.');
        }
        
        function showCropModal(file, reason) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const cropImage = document.getElementById('cropImage');
                originalImageData = e.target.result;
                pristineImageData = e.target.result;  // Store pristine copy
                cropImage.src = originalImageData;
                
                // Show reason
                document.getElementById('cropReason').textContent = reason;
                
                // Reset touchup flag and adjustments (but don't apply yet)
                touchupApplied = false;
                adjustments = {
                    brightness: 0,
                    contrast: 0,
                    shadows: 0,
                    sharpening: 0
                };
                
                document.getElementById('brightnessSlider').value = 0;
                document.getElementById('brightnessValue').textContent = '0';
                document.getElementById('contrastSlider').value = 0;
                document.getElementById('contrastValue').textContent = '0';
                document.getElementById('shadowsSlider').value = 0;
                document.getElementById('shadowsValue').textContent = '0';
                document.getElementById('sharpeningSlider').value = 0;
                document.getElementById('sharpeningValue').textContent = '0';
                
                // Destroy existing cropper if any
                if (cropper) {
                    cropper.destroy();
                }
                
                // Show modal
                document.getElementById('cropModal').style.display = 'block';
                
                // Initialize cropper after image loads
                cropImage.onload = function() {
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1, // Force square crop
                        viewMode: 1,
                        guides: true,
                        center: true,
                        highlight: true,
                        background: true,
                        autoCropArea: 0.9,
                        responsive: true,
                        restore: true,
                        checkCrossOrigin: true,
                        checkOrientation: true,
                        modal: true,
                        scalable: true,
                        zoomable: true,
                        cropBoxResizable: true,
                        cropBoxMovable: true
                    });
                };
            };
            reader.readAsDataURL(file);
        }
        
        function resetAdjustments() {
            // Reset to pristine image (undo auto touchup as well)
            if (pristineImageData) {
                originalImageData = pristineImageData;
            }
            
            adjustments = {
                brightness: 0,
                contrast: 0,
                shadows: 0,
                sharpening: 0
            };
            
            document.getElementById('brightnessSlider').value = 0;
            document.getElementById('brightnessValue').textContent = '0';
            document.getElementById('contrastSlider').value = 0;
            document.getElementById('contrastValue').textContent = '0';
            document.getElementById('shadowsSlider').value = 0;
            document.getElementById('shadowsValue').textContent = '0';
            document.getElementById('sharpeningSlider').value = 0;
            document.getElementById('sharpeningValue').textContent = '0';
            
            touchupApplied = false;
            
            applyAdjustments();
        }
        
        function applyAdjustments() {
            if (!originalImageData) return;
            
            // If all adjustments are at default, just load the original image without processing
            if (adjustments.brightness === 0 && adjustments.contrast === 0 && 
                adjustments.shadows === 0 && adjustments.sharpening === 0) {
                const cropImage = document.getElementById('cropImage');
                
                // Save current crop data before changing image
                let savedCropData = null;
                if (cropper) {
                    savedCropData = cropper.getData();
                    cropper.destroy();
                }
                
                cropImage.src = originalImageData;
                
                cropImage.onload = function() {
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        guides: true,
                        center: true,
                        highlight: true,
                        background: true,
                        autoCropArea: 0.9,
                        responsive: true,
                        restore: true,
                        checkCrossOrigin: true,
                        checkOrientation: true,
                        modal: true,
                        scalable: true,
                        zoomable: true,
                        cropBoxResizable: true,
                        cropBoxMovable: true,
                        ready: function() {
                            if (savedCropData && savedCropData.width > 0) {
                                cropper.setData(savedCropData);
                            }
                            isApplyingAdjustments = false;
                        }
                    });
                };
                return;
            }
            
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            const img = new Image();
            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                
                let imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                let data = imageData.data;
                
                // Apply brightness
                const brightnessFactor = adjustments.brightness / 100;
                
                // Apply contrast
                const contrastFactor = (adjustments.contrast + 100) / 100;
                
                // Apply shadows (affects darker pixels more)
                const shadowsFactor = adjustments.shadows / 100;
                
                for (let i = 0; i < data.length; i += 4) {
                    let r = data[i];
                    let g = data[i + 1];
                    let b = data[i + 2];
                    
                    // Brightness adjustment
                    r += brightnessFactor * 255;
                    g += brightnessFactor * 255;
                    b += brightnessFactor * 255;
                    
                    // Contrast adjustment
                    r = ((r / 255 - 0.5) * contrastFactor + 0.5) * 255;
                    g = ((g / 255 - 0.5) * contrastFactor + 0.5) * 255;
                    b = ((b / 255 - 0.5) * contrastFactor + 0.5) * 255;
                    
                    // Shadows adjustment (affects darker pixels)
                    const luminance = (r + g + b) / 3;
                    if (luminance < 128) {
                        const shadowStrength = (128 - luminance) / 128;
                        const adjustment = shadowsFactor * shadowStrength * 50;
                        r += adjustment;
                        g += adjustment;
                        b += adjustment;
                    }
                    
                    // Clamp values
                    data[i] = Math.max(0, Math.min(255, r));
                    data[i + 1] = Math.max(0, Math.min(255, g));
                    data[i + 2] = Math.max(0, Math.min(255, b));
                }
                
                // Apply sharpening if needed
                if (adjustments.sharpening > 0) {
                    imageData = applySharpen(imageData, canvas.width, canvas.height, adjustments.sharpening / 100);
                    data = imageData.data;
                }
                
                ctx.putImageData(imageData, 0, 0);
                
                const adjustedDataUrl = canvas.toDataURL('image/jpeg', 0.95);
                const cropImage = document.getElementById('cropImage');
                cropImage.src = adjustedDataUrl;
                
                // Save current crop data (relative to image, not container)
                let savedCropData = null;
                if (cropper) {
                    savedCropData = cropper.getData();
                    cropper.destroy();
                }
                
                cropImage.onload = function() {
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        guides: true,
                        center: true,
                        highlight: true,
                        background: true,
                        autoCropArea: 0.9,
                        responsive: true,
                        restore: true,
                        checkCrossOrigin: true,
                        checkOrientation: true,
                        modal: true,
                        scalable: true,
                        zoomable: true,
                        cropBoxResizable: true,
                        cropBoxMovable: true,
                        ready: function() {
                            // Restore crop position and size if it existed
                            if (savedCropData && savedCropData.width > 0) {
                                cropper.setData(savedCropData);
                            }
                            isApplyingAdjustments = false;
                        }
                    });
                };
            };
            img.src = originalImageData;
        }
        
        function applySharpen(imageData, width, height, amount) {
            const data = imageData.data;
            const output = new ImageData(width, height);
            const outputData = output.data;
            
            // Sharpening kernel
            const kernel = [
                [0, -1 * amount, 0],
                [-1 * amount, 1 + 4 * amount, -1 * amount],
                [0, -1 * amount, 0]
            ];
            
            for (let y = 1; y < height - 1; y++) {
                for (let x = 1; x < width - 1; x++) {
                    let r = 0, g = 0, b = 0;
                    
                    for (let ky = -1; ky <= 1; ky++) {
                        for (let kx = -1; kx <= 1; kx++) {
                            const idx = ((y + ky) * width + (x + kx)) * 4;
                            const k = kernel[ky + 1][kx + 1];
                            r += data[idx] * k;
                            g += data[idx + 1] * k;
                            b += data[idx + 2] * k;
                        }
                    }
                    
                    const outIdx = (y * width + x) * 4;
                    outputData[outIdx] = Math.max(0, Math.min(255, r));
                    outputData[outIdx + 1] = Math.max(0, Math.min(255, g));
                    outputData[outIdx + 2] = Math.max(0, Math.min(255, b));
                    outputData[outIdx + 3] = data[outIdx + 3];
                }
            }
            
            // Copy edges
            for (let x = 0; x < width; x++) {
                for (let edge of [0, height - 1]) {
                    const idx = (edge * width + x) * 4;
                    outputData[idx] = data[idx];
                    outputData[idx + 1] = data[idx + 1];
                    outputData[idx + 2] = data[idx + 2];
                    outputData[idx + 3] = data[idx + 3];
                }
            }
            for (let y = 0; y < height; y++) {
                for (let edge of [0, width - 1]) {
                    const idx = (y * width + edge) * 4;
                    outputData[idx] = data[idx];
                    outputData[idx + 1] = data[idx + 1];
                    outputData[idx + 2] = data[idx + 2];
                    outputData[idx + 3] = data[idx + 3];
                }
            }
            
            return output;
        }
        
        function closeCropModal() {
            document.getElementById('cropModal').style.display = 'none';
            if (cropper) {
                cropper.destroy();
            pristineImageData = null;
                cropper = null;
            }
            // Reset file input
            document.getElementById('photoFile').value = '';
            currentFile = null;
            touchupApplied = false;
            originalImageData = null;
            resetAdjustments();
        }
        
        function autoTouchup() {
            if (!cropper || touchupApplied) return;
            
            // Get the current image data
            const imageElement = document.getElementById('cropImage');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Create a temporary image to get original dimensions
            const img = new Image();
            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                
                // Draw the original image
                ctx.drawImage(img, 0, 0);
                
                // Get image data
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                
                // Apply auto-enhancement
                // 1. Slight brightness boost
                // 2. Contrast enhancement
                // 3. Saturation boost
                const brightnessFactor = 1.1;
                const contrastFactor = 1.15;
                const saturationFactor = 1.2;
                
                for (let i = 0; i < data.length; i += 4) {
                    // Get RGB values
                    let r = data[i];
                    let g = data[i + 1];
                    let b = data[i + 2];
                    
                    // Apply brightness
                    r *= brightnessFactor;
                    g *= brightnessFactor;
                    b *= brightnessFactor;
                    
                    // Apply contrast
                    r = ((r / 255 - 0.5) * contrastFactor + 0.5) * 255;
                    g = ((g / 255 - 0.5) * contrastFactor + 0.5) * 255;
                    b = ((b / 255 - 0.5) * contrastFactor + 0.5) * 255;
                    
                    // Apply saturation
                    const gray = 0.2989 * r + 0.5870 * g + 0.1140 * b;
                    r = gray + (r - gray) * saturationFactor;
                    g = gray + (g - gray) * saturationFactor;
                    b = gray + (b - gray) * saturationFactor;
                    
                    // Clamp values
                    data[i] = Math.max(0, Math.min(255, r));
                    data[i + 1] = Math.max(0, Math.min(255, g));
                    data[i + 2] = Math.max(0, Math.min(255, b));
                }
                
                // Put the modified image data back
                ctx.putImageData(imageData, 0, 0);
                
                // Convert canvas to data URL
                const enhancedDataUrl = canvas.toDataURL('image/jpeg', 0.95);
                
                // Update original image data so sliders work on enhanced image
                originalImageData = enhancedDataUrl;
                
                // Update the image source and reinitialize cropper
                imageElement.src = enhancedDataUrl;
                
                // Save current crop data (relative to image, not container)
                let savedCropData = null;
                if (cropper) {
                    savedCropData = cropper.getData();
                    cropper.destroy();
                }
                
                imageElement.onload = function() {
                    cropper = new Cropper(imageElement, {
                        aspectRatio: 1,
                        viewMode: 1,
                        guides: true,
                        center: true,
                        highlight: true,
                        background: true,
                        autoCropArea: 0.9,
                        responsive: true,
                        restore: true,
                        checkCrossOrigin: true,
                        checkOrientation: true,
                        modal: true,
                        scalable: true,
                        zoomable: true,
                        cropBoxResizable: true,
                        cropBoxMovable: true,
                        ready: function() {
                            // Restore crop position and size if it existed
                            if (savedCropData && savedCropData.width > 0) {
                                cropper.setData(savedCropData);
                            }
                        }
                    });
                    
                    touchupApplied = true;
                    
                    // Show feedback
                    const cropInfo = document.querySelector('.crop-info');
                    const feedback = document.createElement('p');
                    feedback.style.color = '#667eea';
                    feedback.style.fontWeight = 'bold';
                    feedback.textContent = '✓ Auto touchup applied!';
                    cropInfo.appendChild(feedback);
                    
                    setTimeout(() => {
                        feedback.remove();
                    }, 3000);
                };
            };
            
            img.src = imageElement.src;
        }
        
        function applyCrop() {
            if (!cropper) return;
            
            // Get cropped canvas
            const canvas = cropper.getCroppedCanvas({
                width: 800,  // Max width
                height: 800, // Max height (will be square)
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            // Convert to blob
            canvas.toBlob(function(blob) {
                // Create a new File object from the blob
                const croppedFile = new File([blob], currentFile.name, {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                });
                
                // Update the file input with cropped file
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);
                document.getElementById('photoFile').files = dataTransfer.files;
                
                // Hide crop modal and destroy cropper (but don't reset file input)
                document.getElementById('cropModal').style.display = 'none';
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                currentFile = null;
                
                // Automatically proceed with upload
                uploadPhoto();
            }, 'image/jpeg', 0.9);
        }
        
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
            const photoModal = document.getElementById('photoModal');
            const cropModal = document.getElementById('cropModal');
            
            if (event.target == photoModal) {
                closePhotoModal();
            }
            if (event.target == cropModal) {
                closeCropModal();
            }
        }
    </script>
</body>

</html>
