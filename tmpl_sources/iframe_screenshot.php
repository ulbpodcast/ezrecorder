<?php require_once 'global_config.inc';?>

<html>
<head>
</head>
<body style="overflow:hidden">
    <img id="<?php echo $source; ?>_frame" src="" height="160px" border="0" alt="video preview" >
    <!-- "index.php?action=view_screenshot_image&amp;source=<?php echo $source; ?>" > -->
</body>
<script>
    function refresh_preview() {
        var source_type = '<?php echo $source; ?>';
        var img_element = document.getElementById(source_type + '_frame');
        img_element.src = 'index.php?action=view_screenshot_image&source=' + source_type + '&rand=' + Math.random();;
    }
    
    setInterval(function() {
        refresh_preview();
    }, 2000);
    
    refresh_preview(); //also fetch first image right now
    
</script>
</html>