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

$(function() {
    $( "#ocsafe_settings" )
        .click(function() {
            var v_scrambleFileName = $('#scrambleFileName').is(":checked");
            
            $.ajax({
                url: OC.filePath('ocsafe', 'ajax', 'savePersonalSettings.php'),
                async: false,
                timeout: 2000,

                data: {
                    scrambleFileName: v_scrambleFileName
                },

                type: "POST",

                success: function( result ) {
                    if(result !== 'OK') {
                        window.alert(t('ocsafe', 'Settings not saved! Data base error!'))
                    }
                },

                error: function( xhr, status ) {
                    window.alert(t('ocsafe', 'Settings not saved! Communication error!'))
                }                            
            });
        });
    });