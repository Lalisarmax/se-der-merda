<?php
session_start();

// Destruir sessão
session_destroy();

// Limpar localStorage
echo "<script>
    localStorage.removeItem('userLoggedIn');
    localStorage.removeItem('userType');
    localStorage.removeItem('userName');
    localStorage.removeItem('userId');
    window.location.href = 'login.php';
</script>";
exit;
?>
