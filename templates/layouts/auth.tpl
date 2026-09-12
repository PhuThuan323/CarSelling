<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:'Login - CarSelling'}</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    
    <!-- Preload video poster (shows while video loads) -->
    <link rel="preload" href="/assets/images/login-bg-poster.jpg" as="image">
    
    <!-- Meta tags -->
    <meta name="description" content="Login to your CarSelling account">
    <meta name="theme-color" content="#3498db">
</head>
<body class="auth-page">
    {block name="content"}
    <!-- Content goes here -->
    {/block}
    
    <!-- JavaScript -->
    <script src="/assets/js/login.js" defer></script>
    <script src="/assets/js/main.js" defer></script>
</body>
</html>
