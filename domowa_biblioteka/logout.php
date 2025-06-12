<?php
require_once 'config/config.php';

// Zniszcz sesję
session_destroy();
 
// Przekieruj do strony logowania
redirect('/?page=login'); 