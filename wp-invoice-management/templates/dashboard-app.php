<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Invoices | WP Invoices</title>
    <link rel="stylesheet" href="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
    <style>
        body { margin: 0; padding: 0; }
        #wpadminbar { display: none !important; }
        html { margin-top: 0 !important; }
    </style>
</head>
<body>
    <?php
    // Include the dashboard component
    include plugin_dir_path( __FILE__ ) . 'invoice-dashboard.php';
    ?>
    
    <?php wp_footer(); ?>
</body>
</html>
