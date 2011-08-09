<?php
    
    error_reporting( E_ALL );
    
    if ( !file_exists('Ooyala.class.php') ) {
        exit(
            'This page depends on the Ooyala PHP class library. 
             The file "Ooyala.class.php" was not found in the
             directory this script is running from.'
        );
    }
    
    if ( !file_exists('OoyalaViews.class.php') ) {
        exit(
            'This page depends on the Ooyala Views PHP class library. 
             The file "OoyalaViews.class.php" was not found in the
             directory this script is running from.'
        );
    }
    
    require_once 'Ooyala.class.php';
    require_once 'OoyalaViews.class.php';
    
    if ( !class_exists('Ooyala') || !class_exists('OoyalaViews') ) {
        exit(
            'This page depends on the Ooyala PHP class library. 
             The file "Ooyala.class.php" was loaded successfully, 
             but the classes could not be instantiated. If this
             file was modified, please ensure the classes
             "Ooyala" and "OoyalaViews" exist within the library.'
        );
    }
    
    $ooyala_settings = array(
        'pcode' => 'YOUR PRIVATE CODE',
        'scode' => 'YOUR SECRET CODE'
    );
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>Ooyala Dynamic Channels Example</title>
    <style type="text/css"><!--
        * {
            font-family: Calibri, "Segoe UI", Verdana, Tahoma, Arial, sans-serif;
        }
        ul, li {
            margin: 0 0 0 -20px;
            list-style: none;
        }
        ul li ul {
            margin: 0;
            list-style: none;
        }
        form fieldset {
            display: inline;
            float: left;
            width: auto;
            clear: both;
        }
    --></style>
    <script type="text/javascript"><!--
        window.onload = function() {
            // bolden the checked items on, umm, onClick
            var c = document.getElementsByTagName('input');
            for ( var i = 0; i < c.length; i++ ) {
                if ( c[i].getAttribute('type').toLowerCase() == 'checkbox' ) {
                    c[i].onclick = function(e) {
                        if ( this.checked ) {
                            this.parentNode.style.fontWeight = 'bold';
                        }
                        else {
                            this.parentNode.style.fontWeight = 'normal';
                        }
                        this.blur();
                    };
                }
            }
        };
    //--></script>
</head>
<body>
    
    <h1>Ooyala Dynamic Channels Example</h1>
    
    <?php
        
        // initialize the Backlot library
        $backlot = new OoyalaViews(
            $ooyala_settings['pcode'],
            $ooyala_settings['scode']
        );
        
        if ( empty($_GET['labels']) ) {
            
            // request a list of labels from backlot
            // returns FALSE on error
            $labels = $backlot->queryLabelsListNested( array() );
            
            //
            // TODO: more comprehensive error checking
            //       of backlot API response messages
            //
            
            if ( $labels === false ) {
                exit(
                    '<h3>Sorry, but your session timed out.</h3>' .
                    '<p>Please <a href="' . $_SERVER['SCRIPT_NAME'] . '">go back</a>' .
                    ' and start again. If you still have trouble, try' .
                    ' refreshing the page once.</p>'
                );
            }
            
            // show HTML form for choosing labels
            
    ?>
    
    <h3>Dynamic Channel Setup</h3>
    
    <form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="get">
        <!--fieldset>
            <legend>Information</legend>
            <p>Here you can choose what options and labels that will be pulled into the dynamic channel.</p>
            <p><b>Note: you have about <u>one</u> minute to choose some options, or the session will time out.</b></p>
        </fieldset>
        <br clear="all" />
        <br clear="all" /-->
        <fieldset>
            <legend>Player dimensions &amp; options.</legend>
            <label>
                Width:&nbsp;
                <input type="text" name="pwidth" size="4" value="680" />
            </label>
            <br />
            <br />
            <label>
                Height:&nbsp;
                <input type="text" name="pheight" size="4" value="281" />
            </label>
            <br />
            <br />
            <label>
                <input type="checkbox" name="autoplay" value="1" checked="checked" />
                Autoplay
            </label>
        </fieldset>
        <br clear="all" />
        <br clear="all" />
        <fieldset>
            <legend>Choose what labels to import into the channel.</legend>
            <?php
                /*<input type="checkbox" name="labels[]" value="*/
                //foreach ( $labels as $name => $count ) {
                //    // trim leading forward slash
                //    echo PHP_EOL, str_repeat("\t", 4),
                //        '<label>
                //            <input type="checkbox" name="labels[]" value="', $name, '" />',
                //            ((substr($name, 0, 1) != '/') ? $name : substr($name, 1)), " ($count)", '
                //        </label>
                //        <br />';
                //}
                echo $backlot->nestedLabelsToHTMLCheckboxes( $labels );
            ?>
        </fieldset>
        <br clear="all" />
        <br clear="all" />
        <input type="submit" value="Create Player Embed Code" />&nbsp;
        <input type="reset" value="Cancel Changes" />
    </form>
    
    <?php
        
        }
        else {
            
            echo '<h3>Embed Code</h3>';
            
            echo "<pre>", print_r($_GET), "</pre>";
            
            // default player values
            $width = 680;
            $height = 281;
            $autoplay = '0';
            $labels = $_GET['labels'];
            
            if ( count($labels) <= 0 ) {
                exit( '<p>ERROR | you need to choose at least one label.</p>' );
            }
            
            // width override
            if ( !empty($_GET['pwidth']) ) {
                if ( is_numeric(@intval($_GET['pwidth'])) ) {
                    $width = $_GET['pwidth'];
                }
            }
            
            // height override
            if ( !empty($_GET['pheight']) ) {
                if ( is_numeric(@intval($_GET['pheight'])) ) {
                    $height = $_GET['pheight'];
                }
            }
            
            // autoplay override
            if ( !empty($_GET['autoplay']) ) {
                if ( strtolower($_GET['autoplay']) == '1' ) {
                    $autoplay = '1';
                }
            }
            
            // generate dynamic channel player code
            $embedCode = '<script src="http://player.ooyala.com/player.js?width=' .
                $width . '&height=' . $height . '&pcode=' .
                $ooyala_settings['pcode'] . '&labels=' . implode(',', $labels) .
                '&autoplay=' . $autoplay . '&orderBy=uploadTime,desc&browserPlacement=right180px"></script>';
            
            // enable copy & paste
            echo '<textarea rows="10" cols="75">', $embedCode, '</textarea>';
            
            // show preview
            echo '<h2>Channel Preview</h2>';
            echo $embedCode;
            
            
        } // empty GET check
        
    ?>
    
    <!--script src="http://player.ooyala.com/player.js?width=680&height=281&pcode=B2OWY6zz6NRzxvipeZXAKspibgVV&labels=/easter,/Advent%202009&autoplay=1&orderBy=uploadTime,desc&browserPlacement=right180px"></script-->
    
</body>
</html>