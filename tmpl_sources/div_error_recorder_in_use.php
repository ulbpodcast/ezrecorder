<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript">
    var res = false;
    res = window.confirm("®Ongoing_recording®\n\n®Author® : <?php echo $current_user; ?>\n®Course® : <?php echo $course; ?>\n®Start_time® : <?php echo $start_time; ?>"); 
    if(res) {
        window.location = 'index.php?action=recording_force_quit';
    }
    else {
        window.location = 'index.php?action=view_login_form';
    }
</script>