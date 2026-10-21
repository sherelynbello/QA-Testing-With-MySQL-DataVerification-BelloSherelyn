<?php
// Database connection details
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

$conn = new mysqli($servername, $username, $password, $dbname);

$success_message = "";
$error_message   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName   = trim($_POST['FirstName'] ?? '');
    $lastName    = trim($_POST['LastName'] ?? '');
    $govPosition = trim($_POST['GovPosition'] ?? '');
    $location    = trim($_POST['Location'] ?? '');

    // INTENTIONAL FLAW: Omit Location column from INSERT query and bind parameters
    $sql = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $firstName, $lastName, $govPosition);

        if ($stmt->execute()) {
            $success_message = "Success! The wish note has been sent.";
        } else {
            $error_message = "Error executing query: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $error_message = "Database Error: Failed to prepare statement.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kara David's Wish Note</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background-color: #ffffff; width: 100%; max-width: 480px; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border-top: 5px solid #2b5876; }
        .card h2 { color: #2b5876; margin-bottom: 20px; font-size: 24px; text-align: center; }
        .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-success { background-color: #e8f8f5; color: #117a65; border: 1px solid #a3e4d7; }
        .alert-error { background-color: #fadbd8; color: #78281f; border: 1px solid #f5b7b1; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: #4a5568; }
        .form-control { width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #cbd5e0; border-radius: 4px; outline: none; }
        .form-control:focus { border-color: #2b5876; }
        .btn-submit { width: 100%; padding: 12px; background-color: #2b5876; color: #ffffff; border: none; border-radius: 4px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        .btn-submit:hover { background-color: #1e3c72; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; padding: 20px; }
        .modal-content { background-color: #ffffff; width: 100%; max-width: 420px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; }
        .modal-header { background-color: #2b5876; color: #ffffff; padding: 16px 20px; font-size: 18px; font-weight: 600; }
        .modal-body { padding: 20px; }
        .verify-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        .verify-label { font-weight: 600; color: #718096; }
        .modal-footer { padding: 15px 20px; background-color: #f7fafc; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #edf2f7; }
        .btn-modal { padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 4px; border: none; cursor: pointer; }
        .btn-cancel { background-color: #e2e8f0; color: #4a5568; }
        .btn-confirm { background-color: #2b5876; color: #ffffff; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Kara David's Wish Note</h2>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form id="wishNoteForm" action="" method="POST">
            <div class="form-group">
                <label for="FirstName">Target's First Name *</label>
                <input type="text" id="FirstName" name="FirstName" class="form-control" maxlength="30" required>
            </div>
            <div class="form-group">
                <label for="LastName">Target's Last Name *</label>
                <input type="text" id="LastName" name="LastName" class="form-control" maxlength="30" required>
            </div>
            <div class="form-group">
                <label for="GovPosition">Target's Government Position *</label>
                <input type="text" id="GovPosition" name="GovPosition" class="form-control" maxlength="20" required>
            </div>
            <div class="form-group">
                <label for="Location">Target's Location</label>
                <input type="text" id="Location" name="Location" class="form-control" maxlength="40">
            </div>
            <button type="submit" class="btn-submit">Send Wish Note</button>
        </form>
    </div>

    <div id="verifyModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">Verify Target Data</div>
            <div class="modal-body">
                <p style="margin-bottom: 10px;">Please double check the target information before sending:</p>
                <div class="verify-item"><span class="verify-label">First Name:</span><span id="vFirstName"></span></div>
                <div class="verify-item"><span class="verify-label">Last Name:</span><span id="vLastName"></span></div>
                <div class="verify-item"><span class="verify-label">Government Position:</span><span id="vGovPosition"></span></div>
                <div class="verify-item"><span class="verify-label">Location:</span><span id="vLocation"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCancel" class="btn-modal btn-cancel">Cancel</button>
                <button type="button" id="btnConfirm" class="btn-modal btn-confirm">Confirm &amp; Send</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('wishNoteForm');
            const modal = document.getElementById('verifyModal');

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                document.getElementById('vFirstName').textContent = document.getElementById('FirstName').value.trim();
                document.getElementById('vLastName').textContent = document.getElementById('LastName').value.trim();
                document.getElementById('vGovPosition').textContent = document.getElementById('GovPosition').value.trim();
                const loc = document.getElementById('Location').value.trim();
                document.getElementById('vLocation').textContent = loc !== "" ? loc : "(Not provided)";
                modal.style.display = 'flex';
            });

            document.getElementById('btnCancel').addEventListener('click', function () {
                modal.style.display = 'none';
            });

            document.getElementById('btnConfirm').addEventListener('click', function () {
                modal.style.display = 'none';
                form.submit();
            });
        });
    </script>
</body>
</html>
