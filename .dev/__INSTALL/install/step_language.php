<?php

require dirname(__FILE__)."/header.php";

if(isset($_POST["next"])){
	if (isset($_POST["language_select"])) {
		$log_text .= "\n//********* Language **********//\n";
		$log_text .= "Selected language \"".$_POST["language_select"]."\"\n";
		add_log($log_text);
		$_SESSION['INSTALL']["language_select"] = $_POST["language_select"];
	}

	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$next_step);
}


if(isset($_POST["prev"])){
	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$back_step);
}

	include $_SESSION['INSTALL']["install_path"]."template/header.stpl";
	include $_SESSION['INSTALL']["install_path"]."template/step_".$_GET["step"].".stpl";
	include $_SESSION['INSTALL']["install_path"]."template/footer.stpl";

?>