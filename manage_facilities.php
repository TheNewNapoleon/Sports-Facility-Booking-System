<?php
session_start();
require_once 'db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $venue_id = isset($_POST['venue_id']) ? trim($_POST['venue_id']) : '';
    
    if (empty($venue_id)) {
        $message = "Please select a facility.";
        $message_type = "error";
    } else {
        // Validate file
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $message = "Invalid file type. Please upload JPG, PNG, GIF, or WebP.";
            $message_type = "error";
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $message = "File too large. Maximum size is 5MB.";
            $message_type = "error";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = 'images/facilities/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'facility_' . $venue_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Update database
                $stmt = $conn->prepare("UPDATE venues SET image = ? WHERE venue_id = ?");
                $stmt->bind_param("ss", $file_path, $venue_id);
                
                if ($stmt->execute()) {
                    $message = "Image uploaded successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating database: " . $stmt->error;
                    $message_type = "error";
                    unlink($file_path); // Delete uploaded file if DB update failed
                }
                $stmt->close();
            } else {
                $message = "Error uploading file.";
                $message_type = "error";
            }
        }
    }
}

// Get all facilities
$stmt = $conn->prepare("SELECT venue_id, name, image FROM venues ORDER BY name");
$stmt->execute();
$venues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Facility Images</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .upload-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        select, input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        select:focus, input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .facility-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .facility-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .facility-image {
            width: 100%;
            height: 180px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #ddd;
            overflow: hidden;
        }
        
        .facility-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .facility-info {
            padding: 15px;
        }
        
        .facility-info h3 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .facility-info p {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 10px;
            word-break: break-all;
        }
        
        .facility-status {
            padding: 8px 12px;
            background: #e7f3ff;
            color: #0066cc;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .facility-status.no-image {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="header">
            <h1>🖼️ Manage Facility Images</h1>
            <p>Upload and manage images for facility cards</p>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-form">
            <h2 style="margin-bottom: 20px; color: #333;">Upload Facility Image</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="venue_id">Select Facility</label>
                    <select name="venue_id" id="venue_id" required>
                        <option value="">-- Choose a facility --</option>
                        <?php foreach ($venues as $venue): ?>
                            <option value="<?php echo htmlspecialchars($venue['venue_id']); ?>">
                                <?php echo htmlspecialchars($venue['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">Choose Image File</label>
                    <input type="file" name="image" id="image" accept="image/*" required>
                    <p style="font-size: 0.85rem; color: #666; margin-top: 8px;">
                        Supported: JPG, PNG, GIF, WebP (Max 5MB)
                    </p>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Image
                </button>
            </form>
        </div>
        
        <div>
            <h2 style="color: white; margin-bottom: 20px;">Facility Images</h2>
            <div class="facilities-grid">
                <?php foreach ($venues as $venue): ?>
                    <div class="facility-card">
                        <div class="facility-image">
                            <?php if (!empty($venue['image']) && file_exists($venue['image'])): ?>
                                <img src="<?php echo htmlspecialchars($venue['image']); ?>" alt="<?php echo htmlspecialchars($venue['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                        </div>
                        <div class="facility-info">
                            <h3><?php echo htmlspecialchars($venue['name']); ?></h3>
                            <p style="font-size: 0.8rem; color: #999;">ID: <?php echo htmlspecialchars($venue['venue_id']); ?></p>
                            <div class="facility-status <?php echo empty($venue['image']) ? 'no-image' : ''; ?>">
                                <?php echo !empty($venue['image']) && file_exists($venue['image']) ? '✓ Image Uploaded' : '⚠ No Image'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
