<nav class="main-header navbar navbar-expand-md navbar-light navbar-white">

    <div class="container">

      <a href="./dashboard.php" class="navbar-brand">

        <!-- <img src="../dist/img/logoFBIG.png" alt="AdminLTE Logo" class="brand-image image-circle"> -->

        <span class="brand-text font-weight-light">GDIID</span>

      </a>



      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">

        <span class="navbar-toggler-icon"></span>

      </button>



      <div class="collapse navbar-collapse order-3" id="navbarCollapse">

        <!-- Left navbar links -->

        <ul class="navbar-nav">

          <li class="nav-item">

            

          </li>

          <li class="nav-item">

            <a href="#" class="nav-link"><?php echo $user->getuserName();?></a>

          </li>

          <li class="nav-item">

            <a href="#" class="nav-link"><?php echo $user->getuserlevel();?></a>

          </li>

          <!-- <li class="nav-item dropdown">

            <a id="dropdownSubMenu1" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Dropdown</a>

            <ul aria-labelledby="dropdownSubMenu1" class="dropdown-menu border-0 shadow">

              <li><a href="#" class="dropdown-item">Some action </a></li>

              <li><a href="#" class="dropdown-item">Some other action</a></li>

              <li class="dropdown-divider"></li>

            </ul>

          </li> -->

        </ul>

      </div>



      <!-- Right navbar links -->

      <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto"> 

        <li class="nav-item">

          <a class="nav-link" href="./logout.php" >

            log out

          </a>

        </li>

      </ul>

    </div>

  </nav>