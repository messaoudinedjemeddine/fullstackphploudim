<?php
// Get current language
$current_lang = $_SESSION['language'] ?? DEFAULT_LANG;

// Load language file
$lang = [];
if (file_exists("lang/$current_lang.php")) {
    $lang = include "lang/$current_lang.php";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>public/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>
    
    <main>