<?php
session_start();
include "../config/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    
    try {
        $stmt->execute([$name, $email, $pass]);
        $_SESSION['success'] = "Account created! You can now login.";
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Email already exists!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Create an Account</h2>

    <?php 
    if (isset($_SESSION['error'])) { 
        echo "<p style='color:red'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    } 
    ?>

    <form method="POST">
        <input type="text" name="name" required placeholder="Full Name"><br>
        <input type="email" name="email" required placeholder="Email"><br>
        <input type="password" name="password" required placeholder="Password"><br>
        <button type="submit">Register</button>
    </form>

    <a href="login.php">Already have an account? Login</a>
</body>
</html>
