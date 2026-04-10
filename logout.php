<?php
// logout.php
require_once 'config/db.php';
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
</head>
<body>
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { firebaseConfig } from "./js/firebase-config.js";

        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        // Sign out from Firebase if user was logged in
        signOut(auth).then(() => {
            window.location.href = 'index.php';
        }).catch(() => {
            window.location.href = 'index.php';
        });
    </script>
</body>
</html>
