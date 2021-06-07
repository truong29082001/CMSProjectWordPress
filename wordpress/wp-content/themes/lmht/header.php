<!DOCTYPE html>


<html lang="en">
<head>
    <meta charset="<?php bloginfo('charset'); ?>"/>
    <link rel="profile" href="http://gmgp.org/xfn/11" />
    <link rel="pingback" href="<?php bloginfo('pingback_url');?>" />
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <?php wp_head(); ?>
</head>
<body <?php body_class();?> >
<div class="container">

    <header id="topheader" class="clearfix">


     <nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="#">Navbar</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      
        
            <?php 
                wp_nav_menu(
                    array(
                        'menu_id' => 'primary-menu',
                    )
                );
            ?>
<!--      <li class="nav-item">
        <a class="nav-link" href="#">Link</a>
      </li>-->
    </ul>
    <form class="form-inline my-2 my-lg-0">
      <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
      <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
    </form>
  </div>
</nav> 
    
        <div id="main-navigation">
            <?php 
//                wp_nav_menu(
//                    array(
//                        'menu_id' => 'primary-menu',
//                    )
//                );
            ?>
        </div>
        <div id="groupButtonTop">
            <a href="http://taigame.lienminh.garena.vn">Tải Game</a>
            <a href="http://garena.vn/register">Đăng ký</a>
        </div>


    </header>    
