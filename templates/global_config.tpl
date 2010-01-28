<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
    <head>
        <title></title>
    </head>
    <body>
        <div class="dialog_window" id="configuration">
            <h2 class="dialog_heading">
                Configuration
            </h2>
            <form action="torrentwatch.php?setGlobals=1" id="config_form" name="config_form">
                <div class="config_form">
                    <div class="category">
                        <label class="category">Interface Settings</label>
                    </div>
                    <div class="int_settings left">
                        <div id="config_webui">
                            <label class="item select">Choose Font Size:</label> 
                            <select name="webui" id="config_webui" onchange="changeFontSize(this.options[this.selectedIndex].value)">
                                <option value="Small">
                                    Small
                                </option>
                                <option value="Medium" selected>
                                    Medium
                                </option>
                                <option value="Large">
                                    Large
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="int_settings right">
                        <div id="config_combinefeeds">
                            <label class="item checkbox" title="Combine all feeds into 1 list">
                                <input type="checkbox" name="combinefeeds" value="1" <?php echo $combinefeeds; ?>/>
                            Combine Feeds</label>
                        </div>
                        <div id="config_disable_hidelist">
                            <label class="item checkbox" title="Disable the hide list.">
                                <input type="checkbox" name="dishidelist" value="1" <?php echo $dishidelist; ?>/>
                            Disable hide list</label>
                        </div>
                    </div>
                    <div class="category">
                        <label class="category">Torrent Client Settings</label>
                    </div>
                    <div class="tor_client_settings left">
                        <div id="config_torrentclient">
                            <label class="item select" title="Which torrent client to use">Client:</label> 
                            <select name="client" id="client" onchange="changeClient(this.options[this.selectedIndex].value)">
                                <option value="Transmission" <?php echo $transmission; ?>>
                                    Transmission
                                </option>
                                <option value="folder" <?php echo $folderclient; ?>>
                                    Save torrent in folder
                                </option>
                            </select>
                        </div>
                        <div id="config_folderclient">
                            <label class="item">File Extension</label> <input type="text" class="text" name="extension" 
                             value="<?php echo $config_values['Settings']['Extension']; ?>"/>
                        </div>
                        <div id="config_downloaddir" title="Default directory to start items in">
                            <label class="item textinput">Download Dir:</label> <input type="text" class="text" name="downdir"
                             value="<?php echo $config_values['Settings']['Download Dir']; ?>"/>
                        </div>
                        <div id="config_tr_host" title="Hostname">
                            <label class="item textinput">Hostname:</label> <input type="text" class="text" name="trhost"
                             value="<?php echo $config_values['Settings']['Transmission Host']; ?>"/>
                        </div>
                    </div>
                    <div class="tor_client_settings right">
                        <div id="config_tr_port" title="Port">
                            <label class="item textinput">Port:</label> <input type="text" class="text" name="trport" 
                             value="<?php echo $config_values['Settings']['Transmission Port']; ?>"/>
                        </div>
                        <div id="config_tr_user" title="Username">
                            <label class="item textinput">Username:</label> <input type="text" class="text" name="truser"
                             value="<?php echo $config_values['Settings']['Transmission Login']; ?>"/>
                        </div>
                        <div id="config_tr_password" title="Password">
                            <label class="item textinput">Password:</label> <input type="password" class="password" name="trpass"
                             value="<?php echo $config_values['Settings']['Transmission Password']; ?>"/>
                        </div>
                    </div>
                    <div class="category tor_settings">
                        <label class="category" id="torrent_settings">Torrent Settings</label>
                    </div>
                    <div class="tor_settings left">
                        <div id="config_deepdir">
                            <label class="item select" title="Save downloads in multi-directory structure">Deep Directories:</label>
                             <select name="deepdir">
                                <option value="Full" <?php echo $deepfull; ?>>
                                    Full Name
                                </option>
                                <option value="Title" <?php echo $deeptitle; ?>>
                                    Show Title
                                </option>
                                <option value="0" <?php echo $deepoff; ?>>
                                    Off
                                </option>
                            </select>
                        </div>
                        <div id="default_ratio">
                            <label class="item textinput" title="Set default Seed Ratio">Default Seed Ratio:</label>
                            <input type="text" class="text" name="defaultratio"
                             value="<?php echo $config_values['Settings']['Default Seed Ratio']; ?>"/>
                        </div>
                    </div>
                    <div class="tor_settings right">
                        <div id="config_watchdir">
                            <label class="item textinput" title="Directory to look for new .torrents">Watch Dir:</label>
                            <input type="text" class="text" name="watchdir"
                             value="<?php echo $config_values['Settings']['Watch Dir']; ?>"/>
                        </div>
                        <div id="config_savetorrent">
                            <label class="item checkbox" title="Save torrent to download directory">
                                <input type="checkbox" name="savetorrents" value="1" <?php echo $savetorrent; ?>/> 
                            Save torrent files</label>
                        </div>
                    </div>
                    <div class="category">
                        <label class="category">Favorites Settings</label>
                    </div>
                    <div class="fav_settings left">
                        <div id="config_matchstyle">
                            <label class="item select" title="Type of filter to use">Matching Style:</label>
                            <select name="matchstyle">
                                <option value="regexp" <?php echo $matchregexp; ?>>
                                    RegExp
                                </option>
                                <option value="glob" <?php echo $matchglob; ?>>
                                    Glob
                                </option>
                                <option value="simple" <?php echo $matchsimple; ?>>
                                    Simple
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="item checkbox">
                                <input type="checkbox" name="favdefaultall" value="1" <?php echo $favdefaultall; ?>
                                title="Set feed to all when adding favorite. (This doesn't affect existing favorites)"/>
                            Set default feed to "All" for tv shows with episode info</label>
                        </div>
                    </div>
                    <div class="fav_settings right">
                        <div id="config_require_epi_info">
                            <label class="item checkbox" 
                                title="When enabled only shows with episode information (S01E12, 1x12, etc... ) wil be matched.">
                                <input type="checkbox" name="require_epi_info" value="1" <?php echo $require_epi_info; ?>/>
                            Require episode info</label>
                        </div>
                        <div id="config_verifyepisodes" title="Try not to download duplicate episodes">
                            <label class="item checkbox">
                                <input type="checkbox" name="verifyepisodes" value="1" <?php echo $verifyepisode; ?>/>
                            Verify Episodes</label>
                        </div>
                        <div>
                            <label class="item checkbox">
                                <input type="checkbox" name="onlynewer" value="1" <?php echo $onlynewer; ?>/> 
                            Newer Episodes Only</label>
                        </div>
                    </div>
                    <div class="category">
                        <label class="category">Other Settings</label>
                    </div>
                    <div class="other_settings left">
                        <div id="email_address">
                            <label class="item">Email Address:</label>
                            <input type="text" name ="emailAddress" class="text" 
                            title="Enter an email address here to send warnings and errors to."
                            value="<?php echo $config_values['Settings']['Email Address']; ?>"/>
                        </div>
                        <div>
                            <label class="item checkbox">
                                <input type="checkbox" name="mailonhit" value="1" <?php echo $mailonhit; ?>/> 
                            Send mail when favorite torrent is started</label>
                        </div>
                    </div>
                    <div class="fav_settings right">
                        <div id="script">
                            <label class="item">Script:</label>
                            <input type="text" class="text" readonly="readonly"
                            title="Configured script to run on certain events. (Read doc/script.txt for more info)."
                            value="<?php echo $config_values['Settings']['Script']; ?>"/>
                        </div>
                    </div>
                </div>
                <div class="buttonContainer">
                    <a class="submitForm button" id="Save" href="#" name="Save">Save</a> 
                    <a class='toggleDialog button' href='#'>Close</a> 
                    <a class='toggleDialog button' href='#feeds'>Feeds</a>
                    <a class='toggleDialog button' href='#hidelist'>Hide List</a>
                    <a class='toggleDialog button' href='#welcome1'>Wizard</a>
                </div>
            </form>
        </div>
    </body>
</html>
