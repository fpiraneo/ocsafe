/*
 * Copyright 2014 by Francesco PIRANEO G. (fpiraneo@gmail.com)
 * 
 * This file is part of ocsafe.
 * 
 * ocsafe is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * ocsafe is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with ocsafe.  If not, see <http://www.gnu.org/licenses/>.
 */

$(document).ready(function() {
    var ocsDroppedDown = false;
    var ocsPassword = false;
    
    if(typeof FileActions !== 'undefined') {
        var infoIconPath = OC.imagePath('ocsafe','lock.svg');

        FileActions.register('file', 'Safe', OC.PERMISSION_UPDATE, infoIconPath, function(fileName) {
            var extension = fileName.substring(fileName.length - 5);
            var encrypting = (extension !== '.safe');
            var opName = (encrypting) ? t('ocsafe', 'Encrypt') : t('ocsafe', 'Decrypt');

            // Action to perform when clicked
            if(scanFiles.scanning) { return; } // Workaround to prevent additional http request block scanning feedback

            var directory = $('#dir').val();
            directory = (directory === "/") ? directory : directory + "/";

            var filePath = directory + fileName;    
            var message = t('ocsafe', 'Type the password below:');

            var html = '<div id="ocsDropdown" class="drop">\n\
                            <p id="ocsMessage">' + message + '</p>\n\
                            <div id="ocsSubmit">\n\
                                <input id="ocsPassword" style="width:220px" type="password" />\n\
                                <input id="ocsExecute" type="button" value="' + opName + '" />\n\
                            </div>\n\
                        </div>';

            if(fileName) {
                $('tr').filterAttr('data-file',fileName).addClass('mouseOver');
                $(html).appendTo($('tr').filterAttr('data-file', fileName).find('td.filename'));
            }

            $('#ocsDropdown').show('blind');
            $('#ocsafe_sel').chosen();
            $('#ocsExecute').bind('click',function() {
                if(ocsPassword) {
                    var password = $('#ocsPassword').val();
                    doEncrypt(filePath, password);
                }
            });
            
            $('#ocsPassword').bind('keyup', function(eventData) {
                var password = $('#ocsPassword').val();

                if(password === '') {
                    $("#ocsPassword").css("background-color", "red");
                    ocsPassword = false;
                } else {
                    $("#ocsPassword").css("background-color", "white");
                    ocsPassword = true;
                    
                    if(eventData.keyCode == 13) {
                        doEncrypt(filePath, password);
                    }                    
                }
            });
        });
        
        ocsDroppedDown = true;
    }

    $(document).on('click', function(event) {
        var target = $(event.target);
        var clickOut = !(target.is('#ocsPassword') || target.is('#ocsExecute'));

        if(ocsDroppedDown && clickOut) {
            hideDropDown();
        }
    });
    
    function doEncrypt(filePath, password) {
        var extension = filePath.substring(filePath.length - 5);
        var encrypting = (extension !== '.safe');
        var opInProgr = (encrypting) ? t('ocsafe', 'Encrypting...') : t('ocsafe', 'Decrypting...');
        
        $('#ocsSubmit').html('<div style="margin-top: 5px;"><img src="' + OC.imagePath('ocsafe','working.gif') + '" /><span style="margin-left:5px;">' + opInProgr + '</span></div>');

        $.ajax({
            type    : 'POST',
            url     : OC.linkTo('ocsafe', 'ajax/encrypt-decrypt.php'),
            timeout : 2000,
            data    : {
                filePath    : filePath,
                password    : password
            },
            async   : false,
            success : function(result) {
                if(result == 'BADCKS') {
                    window.alert(t('ocsafe', 'Wrong password or corrupted file.'));
                } else if(result == 'KO') {
                    window.alert(t('ocsafe', 'Unable to perform operation! Check the logs.'));
                }

                document.location.reload();    
            },
            error: function(xhr, status) {
                window.alert(t('ocsafe', 'Unable to perform operation! Ajax error.'));
                $(eventData.relatedTarget).addClass('invalid');
            }
        });
    }
    
    function hideDropDown() {
        $('#ocsDropdown').hide('blind',function(){
            $('#ocsDropdown').remove();
            $('tr').removeClass('mouseOver');
        });
    }        
});
