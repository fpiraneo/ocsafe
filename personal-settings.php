<?php
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

OCP\User::checkLoggedIn();

// Handle translations
$l = new \OC_L10N('ocsafe');

$scrambleFileName = intval(OCP\Config::getAppValue('ocsafe', 'scrambleFileName'));

$tmpl = new \OCP\Template('ocsafe', 'personal-settings');
$tmpl->assign('scrambleFileName', ($scrambleFileName === 1) ? 'CHECKED' : '');

return $tmpl->fetchPage();