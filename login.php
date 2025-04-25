<?php
// Страница входа
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenVPN - Вход</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container login-container">
        <h1>OpenVPN Панель управления</h1>
        
        <div class="login-form">
            <h2>Авторизация</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit">Войти</button>
                </div>
            </form>
            
            <p class="note">* В реальном продакшене рекомендуется настроить базовую HTTP-аутентификацию через .htaccess</p>
        </div>
    </div>
</body>
</html> 