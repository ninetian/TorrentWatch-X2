$(function() {
    // Menu Bar, and other buttons which show/hide a dialog
    $("a.toggleDialog").live('click',
    function() {
        $(this).toggleDialog();
    });
    
    // Vary the font-size
    changeFontSize = function(fontSize) {
        var f = fontSize;
        $.cookie('twFontSize', f, { expires: 666 });
        switch (f) {
        case 'Small':
            $("body").css('font-size', '75%');
            break;
        case 'Medium':
            $("body").css('font-size', '85%');
            break;
        case 'Large':
            $("body").css('font-size', '100%');
            break;
        }
    };
    
    displayFilter = function(filter, empty) {
        if(empty == 1) {
            $.fn.hideMe = function() {
                $(this).hide();
            }
        } else {
            $.fn.hideMe = function() {
                $(this).slideUp();
            }
        }
        $.cookie('TWFILTER', filter, { expires: 666 });
        if (filter == 'all') {
            if ($('.transmission').is(":visible")) {
                $('.transmission').hideMe();
                $('.feed .header').slideDown();
            }
            var tor = $(".feed li.torrent").not(".hiddenFeed");
            tor.slideDown();
            $('.hide_item').fadeIn();
            tor.markAlt().closest(".feed div.feed");
        } else if (filter == 'matching') {
            if ($('.transmission').is(":visible")) {
                $('.transmission').hideMe();
                $('.feed .header').slideDown();
            }
            var tor = $(".feed li.torrent").filter(".match_nomatch");
            tor.hideMe();
            $('.hide_item').fadeOut();
            tor = $(".feed li.torrent").not(".match_nomatch");
            tor.slideDown();
            tor.markAlt().closest(".feed div.feed");
        } else if (filter == 'downloading') {
            if ($('.transmission').is(":visible")) {
                $('.transmission').hideMe();
                $('.feed .header').slideDown();
            }
            var tor = $(".feed li.torrent").not('.match_downloading, .match_match');
            tor.hideMe();
            $('.hide_item').fadeOut();
            tor = $(".feed  li.torrent").filter('.match_downloading, .match_match');
            tor.slideDown();
            tor.markAlt().closest(".feed div.feed");
        } else if (filter == 'downloaded') {
            if ($('.transmission').is(":visible")) {
                $('.transmission').hideMe();
                $('.feed .header').slideDown();
            }
            var tor = $(".feed li.torrent").not('.match_cachehit, .match_downloaded');
            tor.hideMe();
            $('.hide_item').fadeOut();
            tor = $(".feed li.torrent").filter('.match_cachehit, .match_downloaded');
            tor.slideDown();
            tor.markAlt().closest(".feed div.feed");
        } else if (filter == 'transmission') {
            if ($('.feed').is(':visible')) {
                $('.feed .header').hideMe();
                $('.feed li.torrent').hideMe();
            }
            $('.transmission').slideDown();
        }
        $.checkHiddenFeeds(1);
        $('#filter_' + filter).addClass('selected').siblings().removeClass("selected");
    };
    // Filter Bar - Buttons
    $("ul#filterbar_container li:not(#filter_bytext)").click(function() {
        if ($(this).is('.selected')) {
            return;
        }
        var filter = this.id;
        $("div#torrentlist_container").show(function() {
            switch (filter) {
            case 'filter_all':
                displayFilter('all');
                $.checkHiddenFeeds(1);
                break;
            case 'filter_matching':
                displayFilter('matching');
                break;
            case 'filter_downloading':
                displayFilter('downloading');
                break;
            case 'filter_downloaded':
                displayFilter('downloaded');
                break;
            case 'filter_transmission':
                displayFilter('transmission');
                break;
            }
        });
    });


    // Filter Bar -- By Text
    $("input#filter_text_input").keyup(function() {
        var filterText = $(this).val().toLowerCase();
        $("li.torrent").addClass('hidden_bytext').each(function() {
            if ($(this).find("span.torrent_name").text().toLowerCase().match(filterText)) {
                $(this).removeClass('hidden_bytext');
            }
        }).markAlt();
    });
    
    // Switching visible items for different clients    
    changeClient = function(client) {
        $(".favorite_seedratio, #config_folderclient").css("display", "none");
        $("#torrent_settings").css("display", "block");
        switch (client) {
        case 'folder':
            $(".config_form .tor_settings, div.category tor_settings, #torrent_settings, div.favorite_savein, #config_tr_user, #config_tr_password, #config_tr_host, #config_tr_port").css("display", "none");
            $("#config_folderclient, #config_downloaddir").css("display", "block");
            $("form.favinfo, ul.favorite");
            $('li#webui').hide();
            window.client = 'folder';
            break;
        case 'Transmission':
            $(".config_form .tor_settings, div.category tor_settings, #config_tr_user, #config_tr_password, #config_tr_host, #config_tr_port, #config_downloaddir, div.favorite_seedratio, div.favorite_savein").css("display", "block");
            $("ul.favorite").css("height", 245);
            $.get('torrentwatch.php', { get_tr_location: 1 }, function(uri) {
                if(uri.match(/localhost/) || uri.match(/127.0.0.1/)) { 
                    target = 'http://' + location.hostname + uri.match(/:\d+/) + '/transmission/web/';
                } else {
                    target = 'http://' + uri.match(/\S+:\d+/) + '/transmission/web/';
                }
                $("#webui a").text(client)[0].href = target;
                $('li#webui').show();
            })
            window.client = 'Transmission';
            break;
        }
    };
    
    // Perform the first load of the dynamic information
    $.get('torrentwatch.php', '', $.loadDynamicData, 'html');

    // Configuration, wizard, and update/delete favorite ajax submit
    $("a.submitForm").live('click',
    function(e) {
        window.input_change = 0;
        e.stopImmediatePropagation();
        $.submitForm(this);
        if(this.parentNode.id) 
            $('div#' + this.parentNode.id).hide();
    });
    // Clear History ajax submit
    $("a#clearhistory").live('click',
    function() {
        $.get(this.href, '',
        function(html) {
            // $(html).html() is used to strip the outer tag(<div#history></div>) and get the children
            $("div#history").html($(html).html());
        },
        'html');
        return false;
    });
    // Clear Cache ajax submit
    //$("a.clear_cache a:not(.toggleDialog)").click(function() {
    $('a.clear_cache').live('click',
    function(e) {
        $.get(this.href, '', $.loadDynamicData, 'html');
        return false;
    });

    Math.formatBytes = function(bytes) {
        var size;
        var unit;

        // Terabytes (TB).
        if (bytes >= 1099511627776) {
            size = bytes / 1099511627776;
            unit = ' TB';

            // Gigabytes (GB).
        } else if (bytes >= 1073741824) {
            size = bytes / 1073741824;
            unit = ' GB';

            // Megabytes (MB).
        } else if (bytes >= 1048576) {
            size = bytes / 1048576;
            unit = ' MB';

            // Kilobytes (KB).
        } else if (bytes >= 1024) {
            size = bytes / 1024;
            unit = ' KB';

            // The file is less than one KB
        } else {
            size = bytes;
            unit = ' B';
        }

        // Single-digit numbers have greater precision
        var precision = 2;
        size = Math.roundWithPrecision(size, precision);

        // Add the decimal if this is an integer
        if ((size % 1) === 0 && unit != ' bytes') {
            size = size + '.0';
        }

        return size + unit;
    };

    Math.roundWithPrecision = function(floatnum, precision) {
        return Math.round(floatnum * Math.pow(10, precision)) / Math.pow(10, precision);
    };

    torStartStopToggle = function(torHash) {
        var curObject = $('li.' + torHash + ' p.torStart');
        if (curObject.is(":visible")) {
            curObject.hide();
        } else {
            curObject.show();
        }
        curObject = $('li.' + torHash + ' p.torStop');
        if (curObject.is(":visible")) {
            curObject.hide();
        } else {
            curObject.show();
        }
        curObject = null;
    };
    
    toggleTorMove = function(torHash) {
        var curObject = $('div#move_' + torHash);
        if (curObject.is(":visible")) {
            curObject.hide();
        } else {
            curObject.show();
        }
        curObject = null;
    };
    
    getClientItem = function(item, clientData, liClass) {     
        var hideStop;
        var hideStart;
        if(item.status == 16) {
              hideStop = 'hidden';
        } else {
              hideStart = 'hidden';
        }
        
        var transmissionItem =
        '<li id="clientId_' + item.id + '" class="torrent match_transmission ' + item.hashString + ' ' + liClass +'">' +
        '<table width="100%" cellspacing="0"><tr><td class="buttons left match_transmission">' +
        '<p title="Resume" class="button torStart ' + hideStart + '">' +
        '<a href="#" onclick="$.stopStartTorrent(\'start\', \'' + item.hashString + '\');">' +
        '<img height=10 src="images/tor_start.png" /></a></p>' +
        '<p title="Pause download" class="button torStop ' + hideStop + '">' +
        '<a href="#" onclick="$.stopStartTorrent(\'stop\', \'' + item.hashString + '\');">' +
        '<img height=10 src="images/tor_pause.png" /></a></p>' +
        '<p title="Delete torrent but keep data" class="button torDel">' +
        '<a href="#" onclick="$.delTorrent(\'' + item.hashString + '\', \'false\');">' +
        '<img height=10 src="images/tor_stop.png" /></a></p>' +
        '</td><td class="buttons right match_transmission">' +
        '<p title="Set location or move torrent data.&#13;Current location: ' + item.downloadDir + '" class="button torMove">' +
        '<a href="#" onclick="toggleTorMove(\'' + item.hashString + '\');"><img height=10 src="images/tor_move.png" /></a></p>' +
        '<p title="Delete torrent and its data" class="button torTrash">' +
        '<a href="#" onclick="$.delTorrent(\'' + item.hashString + '\', \'true\');">' +
        '<img height=10 src="images/tor_trash.png" /></a></p>' +
        '</td><td class="torrent_name"><span class="torrent_name">' + item.name + '</span>' +
        '<span class="dateAdded hidden">' + item.addedDate + '</span>' +
        '<div id=tor_' + item.id + ' class="torInfo tor_' + item.hashString + '">' + clientData + '</div>' +
        '<div id="move_' + item.hashString + '" class="move_data hidden">' +
        '<input id="moveTo' + item.hashString + '" type="text" class="text" name="moveTo" value="' + item.downloadDir + '" />' +
        '<a class="move" id="Move" href="#" onclick="$.moveTorrent(\'' + item.hashString + '\')">Move</a>' +
        '<a class="close" href="#" onclick="toggleTorMove(\'' + item.hashString + '\');">-</a>' +
        '</div></td></tr></table></li>';

        return(transmissionItem);
    };

    showClientError = function(error) {
        $('div#clientError p').html('Torrent client returned no or bad data:<br />' + error);
        $('div#clientError').show();
    }

    getClientData = function() {
        if(window.client == 'Transmission') {
            var recent;
            if(window.gotAllData) { recent = 1; } else { recent = 0; };
            
            window.hideProgressBar = recent;
            $.get('torrentwatch.php', {
                'getClientData': 1,
                'recent': recent
            },
                function(json) {
                var check = json.match(/\S+/);
                if(check == 'null') {
                    showClientError('Got no data from ' + window.client);
                    return;
                }

                try { json = JSON.parse(json); }
                catch(err) { 
                showClientError(json);
                    return;
                }
                
                if(recent === 0 && json.result == 'success') window.gotAllData = 1;
                processClientData(json, recent);
                
                if(json && recent) {                    
                    $.each(json['arguments']['removed'],
                    function(i, item) {
                        if ($('li.clientId_' + item).length !== 0) {
                            $('li.clientId_' + item + ' div.torInfo').remove();
                            $('li.clientId_' + item + ' p.activeTorrent').hide();
                            $('li.clientId_' + item + ' p.dlTorrent').show();
                            $('li.clientId_' + item + ' td.buttons').removeClass('match_downloading match_downloaded match_cachehit')
                            .addClass('match_old_download');
                            $('li.clientId_' + item).removeClass('clientId_' + item);
                        }
                        if ($('#transmission_data li#clientId_' + item).length !== 0) {
                            $('#transmission_data li#clientId_' + item).remove();
                        }
                    });
                }
            });
            window.hideProgressBar = null;
        }
    };

    processClientData = function(json, recent) {
        if (json === null) {
            $('div#clientError p').html('Torrent client dit not return any data.<br/>' +
                                    'This usualy happens when the client is not active.');
            $('div#clientError').show();
            window.errorActive = 1;
            return;
        };
        
        if(window.errorActive = 1) {
            $('div#clientError').hide();
            window.errorActive = null;
        }
        
        var oldStatus;
        var liClass;
        var clientData;
        var clientItem;
        var torListHtml = "";
	var upSpeed = 0;
	var downSpeed = 0;
        
        if(!(window.oldStatus)) window.oldStatus = [];
        if(!(window.oldClientData)) window.oldClientData = [];
        
        $.each(json['arguments']['torrents'],
        function(i, item) {
            var Ratio = Math.roundWithPrecision(item.uploadedEver / item.downloadedEver, 2);
            var Percentage = Math.roundWithPrecision(((item.totalSize - item.leftUntilDone) / item.totalSize) * 100, 2);
            var validProgress = Math.roundWithPrecision((100 * item.recheckProgress), 2);

            if (!(Ratio > 0)) {
                Ratio = 0;
            }
            if (!(Percentage > 0)) {
                Percentage = 0;
            }

            if (item.errorString) {
                clientData = item.errorString;
                liClass = "paused";
            } else if (item.status == 1) {
                clientData = 'Waiting to verify';
                liClass = 'waiting';
            } else if (item.status == 2) {
                clientData = 'Verifying files (' + validProgress + '%)';
                liClass = 'verifying';
            } else if (item.status == 4) {
                clientData = 'Downloading from ' + item.peersSendingToUs + ' of ' +
                item.peersConnected + ' peers: ' +
                Math.formatBytes(item.totalSize - item.leftUntilDone) + ' of ' +
                Math.formatBytes(item.totalSize) +
                ' (' + Percentage + '%)  -  Ratio: ' + Ratio;
                liClass = "downloading";
            } else if (item.status == 8) {
                clientData = 'Seeding to ' + item.peersGettingFromUs + ' of ' +
                item.peersConnected + ' peers  -  Ratio: ' + Ratio;
                liClass = 'alt';
            } else if (item.status == 16) {
                if(Ratio >= item.seedRatioLimit && Percentage == 100) {
                    clientData = "Downloaded and seed ratio met. This torrent can be removed.";
                } else {
                    clientData = "Paused";
                }
                liClass = 'paused';
            }
            
            if(recent == 1) {
                clientItem = getClientItem(item, clientData, liClass);
                
                if ($('#transmission_list li#clientId_' + item.id).length === 0) {
                        $('#transmission_list').prepend(clientItem);
                }
                
                if(window.oldClientData[item.id] != clientData) {
                    $('li.' + item.hashString + ' div.torInfo').text(clientData);
                }
                
                if(window.oldStatus[item.id] != item.id + '_' + item.status) {  
                    if (item.status == 16 || item.errorString) {
                        $('li.' + item.hashString + ' p.torStop').hide();
                        $('li.' + item.hashString + ' p.torStart').show();
                    } else {
                        var curTorrent = $('li.' + item.hashString + ' p.torStart');
                        if (curTorrent.is(":visible")) {
                            torStartStopToggle(item.hashString);
                        }
                    }

                    $('li.' + item.hashString).addClass('clientId_' + item.id);

                    $('#transmission_list li#clientId_' + item.id)
                        .removeClass('paused downloading verifying waiting alt').addClass(liClass)                    
                    
                    if (item.leftUntilDone === 0) {
                        $('.' + item.hashString + '.match_downloading')
                        .removeClass('match_downloading').addClass('match_cachehit');
                    }
                }
                
            } else {
                torListHtml += getClientItem(item, clientData, liClass);
                $('#transmission_list').empty();
                $('li.' + item.hashString).addClass('clientId_' + item.id);
            }
            
	    upSpeed = upSpeed + item.rateUpload;
	    downSpeed = downSpeed + item.rateDownload;

            window.oldClientData[item.id] = clientData;
            window.oldStatus[item.id] = item.id + '_' + item.status;
        });

	if(!isNaN(downSpeed) && !isNaN(upSpeed)) {
		$('li#rates').html('D: ' + Math.formatBytes(downSpeed) + '/s<br />' + 'U: ' + Math.formatBytes(upSpeed) + '/s');
	}
        
        if(recent === 0 && torListHtml) {
            $('#transmission_list').append(torListHtml);
        } 
        
        if(!$('.move_data').is(':visible')) {
            $('#transmission_list>li').tsort('span.dateAdded', { order: 'desc' });
        }
    };

    $(document).ready(function() { 
        $.get('torrentwatch.php', { get_client: 1 }, function(client) {
            window.client = client
            changeClient(client);
            fontSize = $.cookie('twFontSize');
            changeFontSize(fontSize);
        })
        setTimeout(function() {
            setInterval(function() {
                getClientData();
            },6000);            
        },2000); 
    });

    // Ajax progress bar
    $("#progressbar").ajaxStart(function() {
        if (!(window.hideProgressBar)) {
            $(this).show();
        }
    }).ajaxStop(function() {
        $(this).hide();
    });
});

(function($) {
    var current_favorite,
    current_dialog;
    // Remove old dynamic content, replace it with passed html(ajax success function)
    $.loadDynamicData = function(html) {
        window.gotAllData = 0;
        $("#dynamicdata").remove();
	$('ul#mainoptions li').removeClass('selected')
        setTimeout(function() {
            var dynamic = $("<div id='dynamicdata' class='dyndata'/>");
            // Use innerHTML because some browsers choke with $(html) when html is many KB
            dynamic[0].innerHTML = html;
            dynamic.find("ul.favorite > li").initFavorites().end()
            .find("form").initForm().end().initConfigDialog().appendTo("body");
            setTimeout(function() {
                var container = $("#torrentlist_container");
                if (container.length === 0 && $("#errorDialog").length === 0) {
                    current_dialog = '#welcome1';
                    $(current_dialog).show();
                } else {
                    container.slideDown(400,
                    function() {
                        $('#torrentlist_container').height($(window).height() - $('#torrentlist_container').attr('offsetTop'));
                    });
                }
                $.get('torrentwatch.php', { show_donate: 1 }, function(donate) {
                    $.get('torrentwatch.php', { show_footer: 1 }, function(footer) {
                    $('#torrentlist_container').append(footer);
                    $('#torrentlist_container').append(donate);
                })
                })
            },
            50);
            var filter = $.cookie('TWFILTER');
            if (!(filter)) {
                filter = 'all';
            }
            if($('#torrentlist div.header').length == 0) {
                $('#torrentlist>li').tsort('#unixTime', {order: 'desc'});
            }
            setTimeout(function() {
                if ($('#transmission_data').length > 0) {
                    $('a#torClient ').show().html('Transmission');
                } else {
                    $('a#torClient').hide();
                    $('p.activeTorrent.delete').hide();
                    $('p.activeTorrent.trash').hide();
                    if(filter == 'transmission') {
                        filter = 'all';
                    }
                }                
                displayFilter(filter, 1);
                var clientCheck = 1;
                if($('#fav_error').length > 0) {
                    setTimeout(function() {
                        $('#fav_error').hide();
                    }, 7000);
                }
                setInterval(function() {
                    if(window.client && clientCheck == 1) {
                        setTimeout(getClientData, 500);
                        clientCheck = null;
                    }
                }, 100);
                $.get('torrentwatch.php', { version_check: 1 }, function(data) {
                    $('#dynamicdata').append(data);
                    setTimeout(function() {
                        $('#newVersion').remove();
                    }, 15000);
        		    var versionCheck = $.cookie('VERSION-CHECK');
                    if(versionCheck != 1) $.cookie('VERSION-CHECK', '1', { expires: 1 });
                })
            },
            100);
        },
        100);
    };
    
    $(window).resize(function() {
        $('#torrentlist_container').height($(window).height() - $('#torrentlist_container').attr('offsetTop'));
    });
    
    $.submitForm = function(button) {
        var form;
        if ($(button).is('form')) {
            // User pressed enter
            form = $(button);
            button = form.find('a')[0];
        } else {
            form = $(button).closest("form");
        }
        //if ((button.id == "Delete") || (button.id == "Update")) {
        if (button.id == "Delete") {
            $.get(form.get(0).action, form.buildDataString(button));
            if (button.id == "Delete") {
                if(button.href.match(/#feedItem/)) {
                    var id = button.href.match(/#feedItem_(\d+)/)[1];
                    $("#feedItem_" + id).remove();
                    $("#feed_" + id).remove();
                }
                if(button.href.match(/#favorite/)) {
                    var id = button.href.match(/#favorite_(\d+)/)[1];
                    $("#favorite_" + id).toggleFavorite();
                    $("#favorite_" + id).remove();
                    $("#fav_" + id).remove();
                    window.dialog = 1;
                }
	     }
        } else {
        	$.get(form.get(0).action, form.buildDataString(button), $.loadDynamicData, 'html');
    	}
    };

    $.fn.toggleDialog = function() {
        this.each(function() {
            if(window.input_change && this.text != 'Next') {
                var answer = confirm('You have unsaved changes.\nAre you sure you want to continue?');
                if(!(answer)) return;
                window.input_change = 0;
            }
            var last = current_dialog === '#' ? '': current_dialog;
            var target = this.hash === '#' ? '#' + $(this).closest('.dialog_window').id: this.hash;
            current_dialog = target === last && window.dialog === 1 ? '': this.hash
            if (last) {
                $(last).fadeOut("normal");
                $('#favorites, #configuration, #feeds, #history, #hidelist').remove();
		$('ul#mainoptions li').removeClass('selected')
            }
            if (current_dialog && this.hash != '#') {
                $.get('torrentwatch.php', { get_dialog_data: this.hash }, function(data) {
                    $('#dynamicdata.dyndata').append(data);
                    $('#dynamicdata').find("ul.favorite > li").initFavorites().end().find("form").initForm().end().initConfigDialog();
                    $(current_dialog).fadeIn("normal");
                    setTimeout(function() {
                        $(".dialog_window input, .dialog_window select").change(function() {
                          window.input_change = 1;
                        });    
                    },500);
                    window.dialog = 1;
                    $(current_dialog + ' a.submitForm').click(function() { window.dialog = 0 })
                });
	        $("li#" + this.parentNode.id).addClass("selected");
            }
        });
        return this;
    };
        
    $.fn.initFavorites = function() {
        var selector = this.selector;
        setTimeout(function() {
            $(selector + ":first a").toggleFavorite();
            $('#favorite_new a#Update').addClass('disabled').removeClass('submitForm');
        },
        300);
    	this.not(":first").tsort('a');
        return this.not(":first").end().click(function() {
            $(this).find("a").toggleFavorite();
        });
    };
    $.fn.initForm = function() {
        this.submit(function(e) {
            e.stopImmediatePropogation();
            $.submitForm(this);
            return false;
        });
        var f = $.cookie('twFontSize');
        if (f) {
            this.find("#config_webui").val(f).change();
        }
        return this;
    };
    $.fn.toggleFavorite = function() {
        this.each(function() {
            if(window.input_change) {
                var answer = confirm('You have unsaved changes.\nAre you sure you want to continue?');
                if(!(answer)) return;
                window.input_change = 0;
            }
            var last = current_favorite;
            current_favorite = this.hash;
            $("#favorites input").keyup(function() {
                if($(current_favorite + ' input:text[name=name]').val().length != 0 && 
                    $(current_favorite + ' input:text[name=filter]').val().length != 0) {
                    $(current_favorite + ' a#Update').removeClass('disabled').addClass('submitForm');                    
                } else {
                    $(current_favorite + ' a#Update').addClass('disabled').removeClass('submitForm');
                }
            });
            
            if (!last) {
                $(current_favorite).show();
            } else {
                $(last).fadeOut(400,
                function() {
                    $(current_favorite).fadeIn(400);
                    $(current_favorite).resetForm();
                });
            }
        });
        return this;
    };
    $.fn.initConfigDialog = function() {
        $('select#client').change();
        return this;
    };
    $.fn.buildDataString = function(buttonElement) {
        var dataString = $(this).filter('form').serialize();
        if (buttonElement) {
            dataString += (dataString.length === 0 ? '': '&') + 'button=' + buttonElement.id;
        }
        return dataString;
    };
    $.fn.markAlt = function() {
        return this.filter(":visible").removeClass('alt').filter(":visible:even").addClass('alt');
    };

    $.addFavorite = function(url) {
        $.get(url, '', $.loadDynamicData, 'html');
    };

    $.dlTorrent = function(url, id) {
        $.get(url,
        function(torHash) {
            if(!(torHash.match(/\w+/)) && window.client != 'folder') {
                alert('Something went wrong while adding this torrent, torrent not added');
                return;
            }
            $('li#' + id).removeClass('match_nomatch').addClass('match_downloading');
            $('li#' + id + ' td.buttons').removeClass('match_nomatch').addClass('match_downloading');
            if ($('li#' + id + ' div.torInfo').length === 0) {
                $('li#' + id + ' td.torrent_name')
                .append('<div id=tor_' + id + ' class="torInfo tor_' + torHash.match(/\w+/) + '"></div>');
            }

	    $('li#' + id + ' span.hide_item').hide();
            $('li#' + id + ' p.dlTorrent').hide();
            $('li#' + id + ' p.torStop').show();
	    if(window.client == 'folder') return;
            $('li#' + id + ' p.trash').show();
            $('li#' + id + ' p.delete').show();

            $('li#' + id).removeClass('###torHash###').addClass(torHash);

            var p = $('li#' + id + ' p.delete');
            p.html(p.html().replace(/###torHash###/g, torHash.match(/\w+/)));
            p = $('li#' + id + ' p.trash');
            p.html(p.html().replace(/###torHash###/g, torHash.match(/\w+/)));
            p = $('li#' + id + ' p.torStart');
            p.html(p.html().replace(/###torHash###/g, torHash.match(/\w+/)));
            p = $('li#' + id + ' p.torStop');
            p.html(p.html().replace(/###torHash###/g, torHash.match(/\w+/)));
            getClientData();
        });
    };

    $.delTorrent = function(torHash, trash) {
        if(trash == 'true') {
            sure = confirm('This will remove the torrent along with its data.\nAre you sure?');
        } else {
            sure = 1;
        }
        
        if(sure) {
            $.getJSON('torrentwatch.php', {
                'delTorrent': torHash,
                'trash': trash
            },
            function(json) {
                if (json.result == "success") {
                    getClientData();
                } else {
                    alert('Request failed');
                }
            });
        }
    };

    $.stopStartTorrent = function(stopStart, torHash) {
        var param;
        if (stopStart == 'stop') {
            param = {
                'stopTorrent': torHash
            };
        } else if (stopStart == 'start') {
            param = {
                'startTorrent': torHash
            };
        }
        $.getJSON('torrentwatch.php', param,
        function(json) {
            if (json.result == "success") {
                $('li.' + torHash + ' p.dlTorrent').hide();
                torStartStopToggle(torHash);
                getClientData();
            } else {
                alert('Request failed');
            }
        });
    };

    $.moveTorrent = function(torHash) {
        var path = $('input#moveTo' + torHash)[0].value;
        
        $.getJSON('torrentwatch.php', {
            'moveTo': path,
            'torHash': torHash
        },
        function(json) {
            toggleTorMove(torHash);
            getClientData();
        });
    };
    
    $.toggleFeedNameUrl = function(idx) {
        $('div.feeditem .feed_name').toggle();
        $('div.feeditem .feed_url').toggle();
        $('#feedNameUrl .item').toggle();        
    }
    
    $.hideItem = function(title) {
        $.get('torrentwatch.php?hide=' + title, '', $.loadDynamicData, 'html');
    }
    
    $.toggleFeed = function(feed, speed) {
        if(speed == 1) {
            if($.cookie('feed_' + feed) == 1) {
                $("#feed_" + feed + " li").show().removeClass("hiddenFeed");
                $("#feed_" + feed + " .header").removeClass("header_hidden");
                $.cookie('feed_' + feed , null, { expires: 666 });
            } else {
                $("#feed_" + feed + " li").hide().addClass("hiddenFeed");
                $("#feed_" + feed + " .header").addClass("header_hidden");    
                $.cookie('feed_' + feed , 1, { expires: 666 });
            }
        } else {
            if($.cookie('feed_' + feed) == 1) {
                $("#feed_" + feed + " li").slideDown().removeClass("hiddenFeed");
                $("#feed_" + feed + " .header").removeClass("header_hidden");
                $.cookie('feed_' + feed , null, { expires: 666 });
            } else {
                $("#feed_" + feed + " li").slideUp().addClass("hiddenFeed");
                $("#feed_" + feed + " .header").addClass("header_hidden");    
                $.cookie('feed_' + feed , 1, { expires: 666 });
            }
        }
    }
    
    $.checkHiddenFeeds = function(speed) {
        $.each($('#torrentlist_container .feed'), function() {
            if($.cookie(this.id)) { 
                if(speed == 1) {
                    $("#feed_" + this.id.match(/feed_(\d)/)[1] + " li").hide().addClass("hiddenFeed");
                } else {
                    $("#feed_" + this.id.match(/feed_(\d)/)[1] + " li").slideUp().addClass("hiddenFeed");
                }
                $("#feed_" + this.id.match(/feed_(\d)/)[1] + " .header").addClass("header_hidden");    
            }
        });
    }
    
    $.submitBug = function() {
        $.post('torrentwatch.php?post_bug', $("#report_form").serialize(),
            function(data) {
                if(data.match(/\bError:/)) {
		    $('div#errorDialog').remove();
                    $(document.body).append(data);
		    setTimeout(function() { $('div#errorDialog').remove(); }, 15000);
                } else {
                    $('.dialog_window').remove();
		    $('ul#mainoptions li').removeClass('selected')
		    $(document.body).append('<div id="successDialog" class="dialog_window" style="display: block;">Success: Thank you for this bug report. You will be contacted by mail.</div>');
		    setTimeout(function() { $('div#successDialog').remove(); }, 10000);
                }
            });
        return;
    }
    
    $.toggleConfigTab = function(tab, button) {
        $(".toggleConfigTab").removeClass("selTab");
        $(button).addClass("selTab");
        $(".configTab").hide();
        if ((tab == "#config_feeds") || (tab == "#config_hideList")) {
            $("#config_form").hide();
        } else {
            $("#config_form").show();
        }
	$(tab).animate({opacity: 'toggle'}, 500);
    }
    
    $.noEnter = function(evt) { 
      var evt = (evt) ? evt : ((event) ? event : null); 
      var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null); 
      if ((evt.keyCode == 13) && (node.type=="text"))  {return false;} 
    } 
    document.onkeypress = $.noEnter; 
})(jQuery);

