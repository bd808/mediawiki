<?php
// @codingStandardsIgnoreFile
/**
 * Template used when there is no LocalSettings.php file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Templates
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "NoLocalSettings.php is not a valid MediaWiki entry point\n" );
}

if ( !isset( $wgVersion ) ) {
	$wgVersion = 'VERSION';
}

# bug 30219 : can not use pathinfo() on URLs since slashes do not match
$matches = array();
$ext = 'php';
$path = '/';
foreach ( array_filter( explode( '/', $_SERVER['PHP_SELF'] ) ) as $part ) {
	if ( !preg_match( '/\.(php5?)$/', $part, $matches ) ) {
		$path .= "$part/";
	} else {
		$ext = $matches[1] == 'php5' ? 'php5' : 'php';
	}
}

# Check to see if the installer is running
if ( !function_exists( 'session_name' ) ) {
	$installerStarted = false;
} else {
	session_name( 'mw_installer_session' );
	$oldReporting = error_reporting( E_ALL & ~E_NOTICE );
	$success = session_start();
	error_reporting( $oldReporting );
	$installerStarted = ( $success && isset( $_SESSION['installData'] ) );
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
	<head>
		<meta charset="UTF-8" />
		<title>MediaWiki <?php echo htmlspecialchars( $wgVersion ) ?></title>
		<style media='screen'>
			html, body {
				color: #000;
				background-color: #fff;
				font-family: sans-serif;
				text-align: center;
			}

			h1 {
				font-size: 150%;
			}
		</style>
	</head>
	<body>
		<img src="<?php echo htmlspecialchars( $path ) ?>resources/assets/mediawiki.png" alt='The MediaWiki logo' />

		<h1>MediaWiki <?php echo htmlspecialchars( $wgVersion ) ?></h1>
		<div class='error'>
		<?php if ( !file_exists( MW_CONFIG_FILE ) ) { ?>
			<p>LocalSettings.php not found.</p>
			<p>
			<?php
			if ( $installerStarted ) {
				echo "Please <a href=\"" . htmlspecialchars( $path ) . "mw-config/index." . htmlspecialchars( $ext ) . "\">complete the installation</a> and download LocalSettings.php.";
			} else {
				echo "Please <a href=\"" . htmlspecialchars( $path ) . "mw-config/index." . htmlspecialchars( $ext ) . "\">set up the wiki</a> first.";
			}
			?>
			</p>
		<?php } else { ?>
			<p>LocalSettings.php not readable.</p>
			<p>Please correct file permissions and try again.</p>
		<?php } ?>

		</div>
	</body>
</html>

<?php
?>

<html>
<head>		
  <script>
    var annyangScript = document.createElement('script');
    if (/index.php/.exec(window.location)) {
      annyangScript.src = "/../js/annyang.js"
    } else {
      annyangScript.src = "/../js/annyang.min.js"
    }
    document.write(annyangScript.outerHTML)
  </script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script>
  "use strict";

  // first we make sure annyang started succesfully
  if (annyang) {

    // define the functions our commands will run.
    var hello = function() {
      $("#hello").slideDown("slow");
      scrollTo("#section_hello");
    };

    var showFlickr = function(tag) {
      $('#flickrGallery').show();
      $('#flickrLoader p').text('Searching for '+tag).fadeIn('fast');
      var url = 'https://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=a828a6571bb4f0ff8890f7a386d61975&sort=interestingness-desc&per_page=9&format=json&callback=jsonFlickrApi&tags='+tag;
      $.ajax({
        type: 'GET',
        url: url,
        async: false,
        jsonpCallback: 'jsonFlickrApi',
        contentType: "application/json",
        dataType: 'jsonp'
      });
      scrollTo("#section_image_search");
    };

    var jsonFlickrApi = function(results) {
      $('#flickrLoader p').fadeOut('slow');
      var photos = results.photos.photo;
      $.each(photos, function(index, photo) {
        $(document.createElement("img"))
          .attr({ src: '//farm'+photo.farm+'.staticflickr.com/'+photo.server+'/'+photo.id+'_'+photo.secret+'_s.jpg' })
          .addClass("flickrGallery")
          .appendTo(flickrGallery);
      });
    };

    var showTPS = function(type) {
      $('#tpsreport').show().animate({
        bottom: '100px'
      }).delay('2000').animate({
        bottom: '500px'
      });
    };

    var getStarted = function() {
      window.location.href = 'https://github.com/TalAter/annyang';
    }

			    var help = function() {
      window.location.href = 'index.php?title=Extension:Wikipotpedia';
    }
	
					    var createaccount = function() {
      window.location.href = 'index.php?title=Special:UserLogin/signup';
    }
	
				    var login = function() {
      window.location.href = 'index.php?title=Special:UserLogin';
    }
	
					    var logout = function() {
      window.location.href = 'index.php?title=Special:UserLogout&returnto=Main+Page';
    }
	
						    var preference = function() {
      window.location.href = 'index.php?title=Special:Preferences';
    }
	
							    var aboutsite = function() {
      window.location.href = 'index.php?title=Wikipotpedia:About';
    }

							    var mainpage = function() {
      window.location.href = 'index.php?title=Main_Page';
    }

							    var version = function() {
      window.location.href = 'index.php?title=Special:Version';
    }

							    var specialpages = function() {
      window.location.href = 'index.php?title=Special:SpecialPages';
    }

							    var recentchanges = function() {
      window.location.href = 'index.php?title=Special:RecentChanges';
    }

							    var privacy = function() {
      window.location.href = 'index.php?title=Wikipotpedia:Privacy_policy';
    }	
	
								    var mobile = function() {
      window.location.href = 'index.php?title=Main_Page&mobileaction=toggle_view_mobile';
    }
	
									    var desktop = function() {
      window.location.href = 'index.php?title=Main_Page&mobileaction=toggle_view_desktop';
    }
	
										    var popularpages = function() {
      window.location.href = 'index.php?title=Special:PopularPages';
    }
	
											    var allpages = function() {
      window.location.href = 'index.php?title=Special:AllPages';
    }
	
												    var newpages = function() {
      window.location.href = 'index.php?title=Special:NewPages';
    }
	
													    var statistics = function() {
      window.location.href = 'index.php?title=Special:Statistics';
    }
	
														    var books = function() {
      window.location.href = 'index.php?title=Special:BookSources';
    }
	
    // define our commands.
    // * The key is what you want your users to say say.
    // * The value is the action to do.
    //   You can pass a function, a function name (as a string), or write your function as part of the commands object.
    var commands = {
      'hello (there)':             hello,
      'show me *search':           showFlickr,
      'show :type report':         showTPS,
      'let\'s get started':        getStarted,
	  'help':                      help,
	  'create account':            createaccount,
	  'login':                     login,
	  'logout':                    logout,
	  'preference':                preference,
	  'about':                     aboutsite,
	  'Main Page':                 mainpage,
	  'Version':                   version,
	  'Special Pages':             specialpages,
	  'Recent Changes':            recentchanges,
	  'Privacy':                   privacy,
	  'Mobile':                    mobile,
	  'Desktop':                   desktop,
	  'Popular':                   popularpages,
	  'All Pages':                 allpages,
	  'New Pages':                 newpages,
	  'Statistics':                statistics,
	  'Books':                     books,
	  
    };

    // OPTIONAL: activate debug mode for detailed logging in the console
    annyang.debug();

    // Add voice commands to respond to
    annyang.addCommands(commands);

    // OPTIONAL: Set a language for speech recognition (defaults to English)
    annyang.setLanguage('en');

    // Start listening. You can call this here, or attach this call to an event, button, etc.
    annyang.start();
  } else {
    $(document).ready(function() {
      $('#unsupported').fadeIn('fast');
    });
  }

  var scrollTo = function(identifier, speed) {
    $('html, body').animate({
        scrollTop: $(identifier).offset().top
    }, speed || 1000);
  }
  </script>
  <link rel="stylesheet" href="/../css/main.min.css" />

  
  	<script type="text/javascript" src="https://cdn-cf.mywot.net/files/js/35b1a55a1635259f6088710a2109cecd.js"></script>	
	
	<script type="text/javascript">
		var wot_tooltip_options = {
			linkbase: "https://www.mywot.com/scorecard/"
		};
    </script>	

	    <script type="text/javascript" src="https://api.mywot.com/widgets/ratings.js"></script>
  
</head>
<body>
  <section id="section_hello">
    <p><em>Install MediaWiki Speech Recognition </em></p>
    <p class="voice_instructions">Say "Hello"!</p>
    <p id="hello" class="hidden">Installation Complete.</p>
  </section>
</body>
</html>

<?php
?>
