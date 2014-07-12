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

namespace OCA\OCSafe;

class safe {
    // Constant value of 1Mbytes in bytes
    // this is a Megabyte - Better a Mebibyte
    private $megaByte = 1048576;
    private $checkString = 'ownCloudSafe';
    private $algorithm = 'blowfish';
    private $eob = '*EOB';
    private $unpadLen = -4;

    // Block size is 4Mb
    private $blockSize;

    /**
     * Class constructor
     */
    function __construct() {
        $this->blockSize = $this->megaByte * 4;
        
        if(!extension_loaded('mcrypt')) {
            throw new \RuntimeException('mcrypt extension not found.');
        }
    }

    /**
     * Set algorithm
     */
    function setAlgorithm($algorithm) {
        if($this->checkAlgorithm) {
            $this->algorithm = $algorithm;
        }
    }

    /**
     * Get algorithm
     */
    function getAlgorithm() {
        return $this->algorithm;
    }

    /**
     * Check algorithm
     */
    function checkAlgorithm($algorithm) {
        $algorythms = mcrypt_list_algorithms();
        $supported = in_array($algorithm, $algorythms);
        return ($supported !== FALSE);
    }

    /**
     * Perform what is needed to get a full encrypted block
     */
    private function encryptBlock($dataBlock, $td, $iv, $password) {
        mcrypt_generic_init($td, $password, $iv);

        $padded = $dataBlock . $this->eob;
        $encryptedData = mcrypt_generic($td, $padded);

        mcrypt_generic_deinit($td);

        return $encryptedData;
    }

    /**
     * Perform what is needed to get a full decrypted block
     */
    private function decryptBlock($blockData, $td, $iv, $password) {
        mcrypt_generic_init($td, $password, $iv);

        $unencryptedData = mdecrypt_generic($td, $blockData);
        $trimmedBlock = rtrim($unencryptedData, "\0");
        $unpadded = substr($trimmedBlock, 0, $this->unpadLen);

        mcrypt_generic_deinit($td);

        return $unpadded;
    }

    /**
     * Encrypt
     */
    function encrypt($user, $filePath, $password, $scrambleFileName) {
        // Create user's view
        $dirView = new \OC\Files\View('/' . $user . '/files');

        // Check if file can be read
        if(!$dirView->isReadable($filePath)) {
            throw new \OCA\OCSafe\FileIOException('Cannot read input file');
        }
        
        // Build destination path
        if($scrambleFileName === 1) {
            $destDir = dirname($filePath);
            $destFileName = MD5(time());
            $outPath = $destDir . '/' . $destFileName . '.safe';
        } else {
            $outPath = $filePath . '.safe';
        }

        // Check if set algorithm is valid and supported
        if(!$this->checkAlgorithm($this->algorithm)) {
            throw new \RuntimeException('Unsupported algorithm');
        }

        // Init some mcrypt stuffs
        $td = mcrypt_module_open($this->algorithm, '', 'cbc', '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

        // Open input file
        $inrsrc = $dirView->fopen($filePath, 'rb');
        if(!$inrsrc) {
            throw new \OCA\OCSafe\FileIOException('Cannot open input file');
        }

        // Open output file
        $outrsrc = $dirView->fopen($outPath, 'wb');
        if(!$outrsrc) {
            throw new \OCA\OCSafe\FileIOException('Cannot open output file');
        }

        // On output file put encrypted original file name
        fwrite($outrsrc, sprintf("iv:%s\n", base64_encode($iv)));

        // On output file put on header the check string
        mcrypt_generic_init($td, $password, $iv);
        $checkString = mcrypt_generic($td, $this->checkString);
        mcrypt_generic_deinit($td);
        fwrite($outrsrc, sprintf("checkstring:%s\n", base64_encode($checkString)));

        // On output file put encrypted original file name
        mcrypt_generic_init($td, $password, $iv);
        $encFileName = mcrypt_generic($td, $filePath);
        mcrypt_generic_deinit($td);
        fwrite($outrsrc, sprintf("filename:%s\n", base64_encode($encFileName)));

        // Encode main file
        $blockNumber = 0;
        while(!feof($inrsrc)) {
            $inBuff = fread($inrsrc, $this->blockSize);
            $encryptedData = $this->encryptBlock($inBuff, $td, $iv, $password);			
            fwrite($outrsrc, sprintf("block:%d,Len:%d\n", $blockNumber++, strlen($encryptedData)));
            fwrite($outrsrc, $encryptedData);
        }

        // Close all mcrypt stuffs
        mcrypt_module_close($td);
        
        // Close all I/O resources
        fclose($inrsrc);
        fclose($outrsrc);

        // Return generated file name
        return $outPath;
    }

    /**
     * Decrypt
     */
    function decrypt($user, $filePath, $password) {
        // Check if set algorithm is valid and supported
        if(!$this->checkAlgorithm($this->algorithm)) {
            throw new \RuntimeException('Unsupported algorithm');
        }

        // Init some mcrypt stuffs
        $td = mcrypt_module_open($this->algorithm, '', 'cbc', '');

        // Create user's view
        $dirView = new \OC\Files\View('/' . $user . '/files');        

        // Check if file can be read
        if(!$dirView->isReadable($filePath)) {
            throw new \OCA\OCSafe\FileIOException('Cannot read input file');
        }

        // Open input file
        $inrsrc = $dirView->fopen($filePath, 'rb');
        if(!$inrsrc) {
            throw new \OCA\OCSafe\FileIOException('Cannot open input file');
        }

        // Read initialization vector
        $numb = fscanf($inrsrc, "iv:%s\n", $ivEncoded);
        if($numb != 1) {
            throw new \RuntimeException('Unable to read init vector - Damaged file?');
        }

        // Init mcrypt engine with read initialization vector
        $iv = base64_decode($ivEncoded);

        // Decrypt and check check string
        $numb = fscanf($inrsrc, "checkstring:%s\n", $rawCheckString);
        if($numb != 1) {
            throw new \RuntimeException('Unable to check checkstring');
        }
        mcrypt_generic_init($td, $password, $iv);
        $checkString = rtrim(mdecrypt_generic($td, base64_decode($rawCheckString)), "\0");
        mcrypt_generic_deinit($td);
        if($this->checkString != $checkString) {
            throw new \OCA\OCSafe\WrongCKStringException('Check string doesn\t match - Wrong password?');
        }

        // Decrypt file name
        $numb = fscanf($inrsrc, "filename:%s\n", $encFileName);
        if($numb != 1) {
            throw new \RuntimeException('Unable to revert file name');
        }
        mcrypt_generic_init($td, $password, $iv);
        $outFilePath = rtrim(mdecrypt_generic($td, base64_decode($encFileName)), "\0");
        mcrypt_generic_deinit($td);

        // Open output file
        $outrsrc = $dirView->fopen($outFilePath, 'wb');
        if(!$outrsrc) {
            throw new \OCA\OCSafe\FileIOException('Cannot open output file');
        }

        // Decode main file
        $expectedBlockNumber = 0;
        while(!feof($inrsrc)) {
            $inBuff = fgets($inrsrc);
            if(strlen($inBuff) > 0) {
                $numb = sscanf($inBuff, "block:%d,Len:%d\n", $blockNumber, $blockLen);

                // Check for expected block number and correct block length
                if($numb != 2) {
                    throw new \OCA\OCSafe\FileIOException('Unable to revert block data - Damaged file?');
                }

                if($blockNumber != $expectedBlockNumber++) {
                    throw new \OCA\OCSafe\FileIOException('Unable to read correct block data - Damaged file?');
                }
                $inBuff = fread($inrsrc, $blockLen);
                $unencryptedData = $this->decryptBlock($inBuff, $td, $iv, $password);
                fwrite($outrsrc, $unencryptedData);
            }
        }

        // Close all mcrypt stuffs
        mcrypt_module_close($td);

        // Close all I/O resources
        fclose($inrsrc);
        fclose($outrsrc);

        // Return generated file name
        return $outFilePath;
    }
}
