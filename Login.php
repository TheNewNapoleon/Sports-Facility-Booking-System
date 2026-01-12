<?php
session_start();
include 'db.php';

$error = '';
$success = '';

// Check for URL error parameter
if (isset($_GET['error']) && $_GET['error'] === 'blacklisted') {
    $error = "Your account has been suspended. Please contact an administrator.";
}

// -------------------------
// LOGIN
// -------------------------
if(isset($_POST['login'])){
    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];

    // Check user from database
    $stmt = $conn->prepare("SELECT user_id, password, role, name, avatar_path, status 
                            FROM users WHERE user_id=? LIMIT 1");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password'])){ // Use password_verify for hashed passwords
            
            // Check user status
            if($user['status'] === 'blacklisted'){
                $error = "Your account has been suspended. Please contact an administrator.";
            } elseif($user['status'] === 'pending'){
                $error = "Your account is pending approval. Please wait for admin approval.";
            } elseif($user['status'] !== 'active'){
                $error = "Your account is not active. Please contact an administrator.";
            } else {
                // Store session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar_path'] = $user['avatar_path'];

                // Redirect based on DB role
                if($user['role'] === 'admin'){
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            }

        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User ID not found.";
    }

    $stmt->close();
}

// -------------------------
// REGISTRATION
// -------------------------
if(isset($_POST['register'])){
    $reg_role = $_POST['reg_role'];
    $reg_user = trim($_POST['reg_user']);
    $reg_username = trim($_POST['reg_username']);
    $reg_email = trim($_POST['reg_email']);
    $reg_pass = $_POST['reg_pass'];
    $reg_repeat_pass = $_POST['reg_repeat_pass'];

    // Basic validation
    if($reg_pass !== $reg_repeat_pass){
        $error = "Passwords do not match.";
    } else {
        // Validate password strength
        $password_errors = [];
        
        if(strlen($reg_pass) < 8){
            $password_errors[] = "at least 8 characters";
        }
        if(!preg_match('/[A-Z]/', $reg_pass)){
            $password_errors[] = "one uppercase letter";
        }
        if(!preg_match('/[a-z]/', $reg_pass)){
            $password_errors[] = "one lowercase letter";
        }
        if(!preg_match('/[0-9]/', $reg_pass)){
            $password_errors[] = "one number";
        }
        if(!preg_match('/[^A-Za-z0-9]/', $reg_pass)){
            $password_errors[] = "one special character";
        }
        
        if(!empty($password_errors)){
            $error = "Password must contain: " . implode(", ", $password_errors) . ".";
        }
        
        // If no password error, proceed with other validations
        if(empty($error)){
            // Validate user ID format based on role
            if($reg_role === 'student'){
                // Student ID must be exactly 7 digits
                if(!preg_match('/^\d{7}$/', $reg_user)){
                    $error = "Student ID must be exactly 7 digits.";
                }
                // Validate student email domain
                if(empty($error) && !preg_match('/@student\.tarc\.edu\.my$/i', $reg_email)){
                    $error = "Student email must end with @student.tarc.edu.my";
                }
            } elseif($reg_role === 'staff'){
                // Staff ID must be exactly 4 digits
                if(!preg_match('/^\d{4}$/', $reg_user)){
                    $error = "Staff ID must be exactly 4 digits.";
                }
                // Validate staff email domain
                if(empty($error) && !preg_match('/@staff\.tarc\.edu\.my$/i', $reg_email)){
                    $error = "Staff email must end with @staff.tarc.edu.my";
                }
            }
            
            // If no format error, proceed with duplicate check
            if(empty($error)){
            // Check if user ID or email already exists
            $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE user_id=? OR email=? LIMIT 1");
            $stmt->bind_param("ss", $reg_user, $reg_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
                if($result->num_rows > 0){
                    $error = "User ID or email already exists.";
                } else {
                    // Hash password
                    $hashed_pass = password_hash($reg_pass, PASSWORD_DEFAULT);

                    // Insert new user
                    $stmt_insert = $conn->prepare("INSERT INTO users (user_id, name, email, password, role, avatar_path, status) VALUES (?, ?, ?, ?, ?, 'images/avatar/default.png', 'pending')");
                    $stmt_insert->bind_param("sssss", $reg_user, $reg_username, $reg_email, $hashed_pass, $reg_role);

                    if($stmt_insert->execute()){
                        $success = "Registration successful! Your account is pending admin approval. You may wait for the 3-5 working days for admin approval.";
                    } else {
                        $error = "Registration failed. Please try again.";
                    }

                    $stmt_insert->close();
                }

            $stmt->close();
        }
        }
    }
}

$conn->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TARUMT Sports Booking</title>
<link rel="stylesheet" href="css/Login.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body <?php 
    if($error || $success) { 
        echo "data-message='".htmlspecialchars($error ?: $success, ENT_QUOTES)."' data-type='".($error ? 'error' : 'success')."'"; 
    } 
?>>

<a href="index.php" class="index-btn"><i class="fa-solid fa-house"></i></a>

<div class="login-container-wrapper">

    <div class="login-box">
        <h2>TARUMT Sports Facilities</h2>

        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="login-tab">Login</button>
            <button class="tab-btn" data-tab="register-tab">Registration</button>
        </div>

        <!-- TAB CONTENT WRAPPER for smooth animations -->
        <div class="tab-content-wrapper">

            <!-- LOGIN FORM -->
            <form method="POST" action="" class="tab-content login-tab active">
                <div class="form-fields">
                    <div class="input-group">
                        <i class="fas fa-id-card lock-icon"></i>
                        <input type="text" name="user_id" placeholder="Enter your ID" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock lock-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                        <i class="fas fa-eye-slash eye-icon" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-button-wrapper">
                    <button type="submit" name="login">Login</button>
                </div>
            </form>

            <!-- REGISTRATION FORM -->
            <form method="POST" action="" class="tab-content register-tab">
                <div class="form-fields">
                    <div class="input-group">
                        <i class="fas fa-user-tag lock-icon"></i>
                        <div class="custom-dropdown" id="roleDropdown">
                            <div class="cd-selected">Select Role</div>
                            <ul class="cd-list">
                                <li data-value="student">Student</li>
                                <li data-value="staff">Staff</li>
                            </ul>
                        </div>
                        <input type="hidden" name="reg_role" id="reg_role" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-id-card lock-icon"></i>
                        <input type="text" name="reg_user" id="reg_user_input" placeholder="User ID" required pattern="\d+" maxlength="7">
                        <small class="input-hint" id="id_hint"></small>
                        <small class="input-error" id="id_error"></small>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-id-card lock-icon"></i>
                        <input type="text" name="reg_username" placeholder="Full name" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-envelope lock-icon"></i>
                        <input type="email" name="reg_email" id="reg_email_input" placeholder="Email" required>
                        <small class="email-guide" id="email_guide"></small>
                        <small class="input-error" id="email_error"></small>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock lock-icon"></i>
                        <input type="password" id="reg_pass" name="reg_pass" placeholder="Password" required>
                        <i class="fas fa-eye-slash eye-icon" id="toggleRegPassword"></i>
                        <small class="password-requirements" id="password_requirements">
                            <i class="fas fa-info-circle"></i> Password must contain: at least 8 characters, 1 uppercase, 1 lowercase, 1 special character and number
                        </small>
                        <small class="input-error" id="password_error"></small>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock lock-icon"></i>
                        <input type="password" id="reg_repeat_pass" name="reg_repeat_pass" placeholder="Repeat Password" required>
                        <i class="fas fa-eye-slash eye-icon" id="toggleRepeatPassword"></i>
                        <small class="input-error" id="repeat_password_error"></small>
                    </div>
                </div>

                <div class="form-button-wrapper">
                    <button type="submit" name="register">Register</button>
                </div>
            </form>

        </div>
        <!-- End tab-content-wrapper -->

    </div>
</div>


<script>
// ===================================
// ROLE-BASED INPUT LABEL AND HINT
// ===================================
const roleConfig = {
    student: {
        placeholder: 'Student ID',
        hint: 'Enter 7-digit Student ID, e.g. 1234567',
        emailPlaceholder: 'Student email',
        emailGuide: 'Use your student email (e.g. abc123@student.tarc.edu.my)',
        emailPattern: /@student\.tarc\.edu\.my$/i
    },
    staff: {
        placeholder: 'Staff ID',
        hint: 'Enter 4-digit Staff ID, e.g. 1234',
        emailPlaceholder: 'Staff email',
        emailGuide: 'Use your Staff email (e.g. abc123@staff.tarc.edu.my)',
        emailPattern: /@staff\.tarc\.edu\.my$/i
    }
};

// ===================================
// CUSTOM DROPDOWN FOR ROLE SELECTION
// ===================================
const roleDropdown = document.getElementById('roleDropdown');
const roleSelected = roleDropdown.querySelector('.cd-selected');
const roleList = roleDropdown.querySelector('.cd-list');
const roleItems = roleDropdown.querySelectorAll('.cd-list li');
const roleInput = document.getElementById('reg_role');
const userInput = document.getElementById('reg_user_input');
const idHint = document.getElementById('id_hint');
const emailInput = document.getElementById('reg_email_input');
const emailGuide = document.getElementById('email_guide');
const emailError = document.getElementById('email_error');

// Toggle dropdown
roleSelected.addEventListener('click', (e) => {
    e.stopPropagation();
    const isActive = roleList.style.display === 'block';
    roleList.style.display = isActive ? 'none' : 'block';
    roleDropdown.classList.toggle('active', !isActive);
});

// Select item
roleItems.forEach(item => {
    item.addEventListener('click', () => {
        const value = item.getAttribute('data-value');
        const text = item.textContent;
        
        roleSelected.textContent = text;
        roleInput.value = value;
        roleList.style.display = 'none';
        
        // Update input placeholder, hint, and validation based on role
        if(roleConfig[value]) {
            userInput.placeholder = roleConfig[value].placeholder;
            idHint.textContent = roleConfig[value].hint;
            idHint.style.display = 'block';
            
            // Update email placeholder and guide
            emailInput.placeholder = roleConfig[value].emailPlaceholder;
            emailGuide.textContent = roleConfig[value].emailGuide;
            emailGuide.style.display = 'block';
            
            // Update maxlength based on role
            if(value === 'student') {
                userInput.setAttribute('maxlength', '7');
            } else if(value === 'staff') {
                userInput.setAttribute('maxlength', '4');
            }
            
            // Clear previous input and error
            userInput.value = '';
            document.getElementById('id_error').textContent = '';
            emailInput.value = '';
            emailError.textContent = '';
            emailInput.classList.remove('error');
        }
    });
});

// ===================================
// USER ID VALIDATION
// ===================================
const idError = document.getElementById('id_error');

userInput.addEventListener('input', function() {
    const value = this.value;
    const role = roleInput.value;
    
    // Clear previous error
    idError.textContent = '';
    this.classList.remove('error');
    
    // Only validate if role is selected
    if(!role) return;
    
    // Only allow digits
    if(value && !/^\d+$/.test(value)) {
        idError.textContent = 'Only numbers are allowed.';
        this.classList.add('error');
        return;
    }
    
    // Validate length based on role
    if(role === 'student' && value.length > 0) {
        if(value.length !== 7) {
            idError.textContent = 'Student ID must be exactly 7 digits.';
            this.classList.add('error');
        }
    } else if(role === 'staff' && value.length > 0) {
        if(value.length !== 4) {
            idError.textContent = 'Staff ID must be exactly 4 digits.';
            this.classList.add('error');
        }
    }
});

// Validate on form submit
document.querySelector('.register-tab').addEventListener('submit', function(e) {
    const role = roleInput.value;
    const userId = userInput.value;
    
    if(role === 'student') {
        if(!/^\d{7}$/.test(userId)) {
            e.preventDefault();
            idError.textContent = 'Student ID must be exactly 7 digits.';
            userInput.classList.add('error');
            userInput.focus();
            return false;
        }
    } else if(role === 'staff') {
        if(!/^\d{4}$/.test(userId)) {
            e.preventDefault();
            idError.textContent = 'Staff ID must be exactly 4 digits.';
            userInput.classList.add('error');
            userInput.focus();
            return false;
        }
    }
});

// ===================================
// EMAIL VALIDATION
// ===================================
emailInput.addEventListener('input', function() {
    const email = this.value;
    const role = roleInput.value;
    
    // Clear previous error
    emailError.textContent = '';
    this.classList.remove('error');
    
    // If no role selected, hide guide
    if(!role) {
        emailGuide.style.display = 'none';
        return;
    }
    
    // If email is empty, show guide
    if(!email) {
        if(roleConfig[role]) {
            emailGuide.textContent = roleConfig[role].emailGuide;
            emailGuide.style.display = 'block';
        }
        return;
    }
    
    // Validate email format based on role
    if(roleConfig[role] && roleConfig[role].emailPattern) {
        if(!roleConfig[role].emailPattern.test(email)) {
            // Hide guide when showing error
            emailGuide.style.display = 'none';
            emailError.textContent = 'Invalid email format. ' + roleConfig[role].emailGuide;
            this.classList.add('error');
        } else {
            // Hide guide when email is valid
            emailGuide.style.display = 'none';
        }
    }
});

// Validate email on form submit
document.querySelector('.register-tab').addEventListener('submit', function(e) {
    const role = roleInput.value;
    const email = emailInput.value;
    
    if(role && roleConfig[role] && roleConfig[role].emailPattern) {
        if(!roleConfig[role].emailPattern.test(email)) {
            e.preventDefault();
            // Hide guide when showing error
            emailGuide.style.display = 'none';
            emailError.textContent = 'Invalid email format. ' + roleConfig[role].emailGuide;
            emailInput.classList.add('error');
            emailInput.focus();
            return false;
        }
    }
});

// ===================================
// PASSWORD VALIDATION
// ===================================
const passwordInput = document.getElementById('reg_pass');
const repeatPasswordInput = document.getElementById('reg_repeat_pass');
const passwordError = document.getElementById('password_error');
const repeatPasswordError = document.getElementById('repeat_password_error');
const passwordRequirements = document.getElementById('password_requirements');

// Password validation function
function validatePassword(password) {
    const errors = [];
    
    if(password.length < 8) {
        errors.push('at least 8 characters');
    }
    if(!/[A-Z]/.test(password)) {
        errors.push('one uppercase letter');
    }
    if(!/[a-z]/.test(password)) {
        errors.push('one lowercase letter');
    }
    if(!/[0-9]/.test(password)) {
        errors.push('one number');
    }
    if(!/[^A-Za-z0-9]/.test(password)) {
        errors.push('one special character');
    }
    
    return errors;
}

// Real-time password validation
passwordInput.addEventListener('input', function() {
    const password = this.value;
    
    // Clear previous error
    passwordError.textContent = '';
    this.classList.remove('error');
    
    if(password.length > 0) {
        const errors = validatePassword(password);
        
        if(errors.length > 0) {
            passwordError.textContent = 'Password must contain: ' + errors.join(', ') + '.';
            this.classList.add('error');
        } else {
            // Password is valid, check if repeat password matches
            if(repeatPasswordInput.value && repeatPasswordInput.value !== password) {
                repeatPasswordError.textContent = 'Passwords do not match.';
                repeatPasswordInput.classList.add('error');
            } else {
                repeatPasswordError.textContent = '';
                repeatPasswordInput.classList.remove('error');
            }
        }
    }
});

// Real-time repeat password validation
repeatPasswordInput.addEventListener('input', function() {
    const password = passwordInput.value;
    const repeatPassword = this.value;
    
    // Clear previous error
    repeatPasswordError.textContent = '';
    this.classList.remove('error');
    
    if(repeatPassword.length > 0) {
        if(password && repeatPassword !== password) {
            repeatPasswordError.textContent = 'Passwords do not match.';
            this.classList.add('error');
        }
    }
});

// Validate passwords on form submit
document.querySelector('.register-tab').addEventListener('submit', function(e) {
    const password = passwordInput.value;
    const repeatPassword = repeatPasswordInput.value;
    
    // Validate password strength
    const errors = validatePassword(password);
    if(errors.length > 0) {
        e.preventDefault();
        passwordError.textContent = 'Password must contain: ' + errors.join(', ') + '.';
        passwordInput.classList.add('error');
        passwordInput.focus();
        return false;
    }
    
    // Validate password match
    if(password !== repeatPassword) {
        e.preventDefault();
        repeatPasswordError.textContent = 'Passwords do not match.';
        repeatPasswordInput.classList.add('error');
        repeatPasswordInput.focus();
        return false;
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', () => {
    roleList.style.display = 'none';
    roleDropdown.classList.remove('active');
});

// ===================================
// CANVA-STYLE HORIZONTAL SLIDE TAB SWITCHING
// ===================================
const tabs = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

// Track current tab index for direction detection
let currentTabIndex = 0;
const tabArray = Array.from(tabContents);

tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => {
        // Prevent clicking the same tab
        if(tab.classList.contains('active')) return;
        
        const target = tab.dataset.tab;
        const targetTab = document.querySelector(`.tab-content.${target}`);
        const currentTab = document.querySelector('.tab-content.active');
        const newTabIndex = tabArray.indexOf(targetTab);
        
        // Determine slide direction
        const isForward = newTabIndex > currentTabIndex;
        
        // Update button states
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        if(currentTab) {
            // Slide current tab out to the left
            currentTab.classList.add('slide-out-left');
            currentTab.classList.remove('active');
            
            // Clean up after animation
            setTimeout(() => {
                currentTab.classList.remove('slide-out-left');
            }, 500);
        }
        
        // Prepare new tab position based on direction
        if(!isForward) {
            targetTab.classList.add('slide-from-left');
        }
        
        // Trigger slide in animation
        setTimeout(() => {
            targetTab.classList.add('active');
            targetTab.classList.remove('slide-from-left');
        }, 50);
        
        // Update current tab index
        currentTabIndex = newTabIndex;
    });
});

// ===================================
// TOGGLE PASSWORD VISIBILITY WITH ICON SWITCH
// ===================================

function setupPasswordToggle(toggleId, inputId) {
    const toggle = document.querySelector(toggleId);
    const input = document.querySelector(inputId);

    // Set default icon to fa-eye-slash (hidden)
    toggle.classList.add('fa-eye-slash');

    toggle.addEventListener('click', function() {
        if(input.type === 'password') {
            // Show password
            input.type = 'text';
            toggle.classList.remove('fa-eye-slash');
            toggle.classList.add('fa-eye');
        } else {
            // Hide password
            input.type = 'password';
            toggle.classList.remove('fa-eye');
            toggle.classList.add('fa-eye-slash');
        }
    });
}

// Apply to all password fields
setupPasswordToggle('#togglePassword', '#password');
setupPasswordToggle('#toggleRegPassword', '#reg_pass');
setupPasswordToggle('#toggleRepeatPassword', '#reg_repeat_pass');


// === TOAST NOTIFICATION ===
document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    const message = body.dataset.message;
    const type = body.dataset.type;

    if(message) {
        const toast = document.createElement('div');
        toast.classList.add('toast', type);
        toast.textContent = message;
        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);

        // Auto-hide after 8 seconds to give users more time to read
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 8000);
    }
});

</script>

</body>
</html>