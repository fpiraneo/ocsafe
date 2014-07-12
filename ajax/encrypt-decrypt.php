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

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('ocsafe');

$filePath = filter_input(INPUT_POST, 'filePath', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

$worker = new OCA\OCSafe\safe();

// Get current user
$user = \OCP\User::getUser();

try {
    // Check if file name ends with '.safe' -> that's means: Encrypted
    if(substr($filePath, -5) === '.safe') {
        $destFileName = $worker->decrypt($user, $filePath, $password);
    } else {
        $scrambleFileName = intval(OCP\Config::getAppValue('ocsafe', 'scrambleFileName'));
        $destFileName = $worker->encrypt($user, $filePath, $password, $scrambleFileName);
    }
} catch(\OCA\OCSafe\WrongCKStringException $exc) {
    die('BADCKS');
} catch(Exception $exc) {
    \OCP\Util::logException('ocsafe', $exc);
    
    die('KO');
}

print 'OK';