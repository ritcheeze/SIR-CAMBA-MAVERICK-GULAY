<?php
$rememberedEmail = isset($_COOKIE['remember_me_user']) ? htmlspecialchars($_COOKIE['remember_me_user'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LSPU Login & Register</title>

  <link rel="stylesheet" href="style.css?v=2">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <!-- BACKGROUND IMAGE -->
  <img src="LSPU.jpg" class="bg-image" alt="LSPU campus background">

  <!-- BLUR OVERLAY -->
  <div class="blur-layer"></div>

  <!-- NAVBAR -->
  <nav>
    <ul>
      <li><a href="#home">Home</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#services">Services</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>

  </nav>

  <!-- MAIN CONTAINER -->
  <div class="container">
    <!-- LEFT SIDE -->
    <div class="left">
      <img src="LSPU LOGO.webp" class="portal-logo" alt="LSPU logo">
      <div class="logo">LSPU Portal</div>

      <h1>Welcome!</h1>

      <p>
        Laguna State Polytechnic University Student Portal.
        Access your account securely and explore university services.
      </p>

    </div>

    <!-- RIGHT SIDE -->
    <div class="right">
      <!-- LOGIN FORM -->
      <div class="form-box login-form">
        <h2>Sign In</h2>

        <!-- UPDATED: Added action, method, and input names -->
        <form action="login.php" method="POST">
          <div class="input-box">
            <input type="email" name="email" value="<?php echo $rememberedEmail; ?>" required>
            <label>Email</label>
          </div>

          <div class="input-box password-box">
            <input id="login_password" type="password" name="password" required>
            <label>Password</label>
            <button type="button" class="password-toggle" onclick="togglePassword('login_password', this)">Show</button>
          </div>

          <div class="remember">
            <label><input type="checkbox" name="remember_me" <?php echo $rememberedEmail !== '' ? 'checked' : ''; ?>> Remember me</label>
            <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
          </div>

          <button type="submit" class="btn">Sign In</button>

          <div class="switch-form">
            Don't have an account?
            <span onclick="showRegister()">Register</span>
          </div>
        </form>
      </div>

      <!-- REGISTER FORM -->
      <div class="form-box register-form">
        <div class="form-header">
          <h2>Register</h2>
          <button type="button" class="symbol-btn" onclick="showLogin()" aria-label="Back to login" title="Back to login">&larr;</button>
        </div>

       <!-- UPDATED: Added action, method, and input names -->
        <form action="register.php" method="POST">
          <div class="input-box">
            <input type="text" name="fullname" required>
            <label>Full Name</label>
          </div>

          <div class="input-box">
            <input type="email" name="email" required>
            <label>Email</label>
          </div>

          <div class="input-box password-box">
            <input id="register_password" type="password" name="password" required>
            <label>Password</label>
            <button type="button" class="password-toggle" onclick="togglePassword('register_password', this)">Show</button>
          </div>

          <div class="input-box password-box">
            <input id="register_confirm_password" type="password" name="confirm_password" required>
            <label>Confirm Password</label>
            <button type="button" class="password-toggle" onclick="togglePassword('register_confirm_password', this)">Show</button>
          </div>

          <div class="input-box select-box">
            <select name="role" required>
              <option value="student" selected>Student</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <button type="submit" class="btn">Create Account</button>


          <div class="switch-form">
            Already have an account?
            <span onclick="showLogin()">Login</span>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- HOME SECTION -->
  <section id="home" class="section home-section">
    <div class="content">
      <h1>Welcome to LSPU Portal</h1>
      <p>
        Explore the official student portal of Laguna State Polytechnic University.
        Access services, announcements, and student resources easily.
      </p>
      <button id="getStartedBtn">Get Started</button>
    </div>
  </section>

  <!-- ABOUT SECTION -->
  <section id="about" class="section about-section">
    <div class="content">
      <h1>About Us</h1>
      <p>
        Laguna State Polytechnic University provides quality education and
        modern technology services for students and faculty members.
      </p>
    </div>
  </section>

  <!-- SERVICES SECTION -->
  <section id="services" class="section services-section">
    <div class="content">
      <h1>Our Services</h1>

      <div class="service-box">
        <h3>Student Portal</h3>
        <p>Access grades, schedules, and announcements online.</p>
      </div>

      <div class="service-box">
        <h3>Enrollment System</h3>
        <p>Fast and secure online enrollment process for students.</p>
      </div>

      <div class="service-box">
        <h3>Library Access</h3>
        <p>Browse digital books and academic resources anytime.</p>
      </div>
    </div>
  </section>

  <!-- CONTACT SECTION -->
  <section id="contact" class="section contact-section">
    <div class="content">
      <h1>Contact Us</h1>

      <form>
        <input type="text" placeholder="Your Name" required>
        <input type="email" placeholder="Your Email" required>
        <textarea placeholder="Your Message"></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>
  </section>

  <script src="index.js"></script>
  <script>
    function togglePassword(fieldId, button) {
      const field = document.getElementById(fieldId);
      const showing = field.type === 'text';

      field.type = showing ? 'password' : 'text';
      button.textContent = showing ? 'Show' : 'Hide';
    }
  </script>
</body>
</html>
