<?php
/**
 * Created by PhpStorm.
 * User: zachortara
 * Date: 1/1/19
 * Time: 9:41 PM
 */
$g_start = time();
define('ROOT', __DIR__);

$config_min_num = 0;
$config_max_num = 3000;

$do_rainbow_case = true; // this will make a lot more entries depending on word length
$do_rot13 = true;

/**
 * Example:
 * $work = 'Test', $min = 0, $max = 5;
 * output will be
 * 'test', 'test0', '0test', '0test0', '0TEST', '0Test', '0tEst'... '0teSt0'... '0tesT0'
 * 'test', 'test1', '1test', '1test1', '1TEST', '1Test', '1tEst'... '1teSt1'... '1tesT1'
 */

// 0. Setup Directories
$input_dir = ROOT . DIRECTORY_SEPARATOR . 'input';
$dict_dir = ROOT . DIRECTORY_SEPARATOR . 'dictionaries';

$directories = [
  $input_dir,
  $dict_dir,
];

foreach ($directories as $directory) {
  if (!is_dir($directory)) {
    !is_dir($directory) && !mkdir($directory) && !is_dir($directory);
  }
}
// 1. Read in files from in directory
$words = [[]];
$found_files = glob($input_dir . DIRECTORY_SEPARATOR . '*.txt');

foreach ($found_files as $found_file) {
  lg('handling file @ ' . $found_file);
  $words[] = file($found_file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
}
$words = array_merge(...$words);

lg('Got ' . count($words) . ' words, working to reduce...');
$words = array_flip(array_flip($words));
lg('Reduced to ' . count($words) . ' unique words');


if ($do_rot13) {
  lg('Adding on words as rot13n\'d');
  $temp = array_map('str_rot13', $words);
  $words = array_merge($words, $temp);
  unset($temp);

  lg('Word list is now ' . count($words) . ' words');
}

$rainbow_words = [];
if ($do_rainbow_case) {
  lg('Converting words to be rainbow\'d');
  foreach ($words as $word) {
    $original_word = strtolower($word);
    $chars = str_split($original_word);
    foreach ($chars as $char_index => $char) {
      $temp = $chars;
      $temp[$char_index] = strtoupper($char);
      $rainbow_words[] = implode('', $temp);
    }
  }
  lg('Created ' . count($rainbow_words) . ' rainbow combinations');
}

$debug_path = ROOT . DIRECTORY_SEPARATOR . 'debug_list.txt';
lg('Outputting reduced list to a file for debugging (' . $debug_path . ')');
file_put_contents($debug_path, implode(PHP_EOL, $words));
// 2. generate patterns and output them to their files

$dict_file_name = date('Ymd_His') . '-custom.dict';
lg('Writing to dictionary file > ' . $dict_file_name);
$out_handle = fopen($dict_dir . DIRECTORY_SEPARATOR . $dict_file_name, 'wb');
$lines_written = 0;

$start = time();

foreach ($words as $word) {
  printf("Writing %s \r", $word);

  $out_lines = 0;
  // in original format
  fwrite($out_handle, $word . PHP_EOL);
  $out_lines++;
  // lower it
  $word = strtolower($word);
  $counter = $config_min_num;
  // plain
  fwrite($out_handle, $word . PHP_EOL);
  fwrite($out_handle, ucwords($word) . PHP_EOL);
  fwrite($out_handle, strtoupper($word) . PHP_EOL);
  $out_lines += 3;

  while ($counter <= $config_max_num) {
    // prefix
    fwrite($out_handle, $counter . $word . PHP_EOL);
    fwrite($out_handle, $counter . ucwords($word) . PHP_EOL);
    fwrite($out_handle, $counter . strtoupper($word) . PHP_EOL);
    $out_lines += 3;
    // suffix
    fwrite($out_handle, $word . $counter . PHP_EOL);
    fwrite($out_handle, ucwords($word) . $counter . PHP_EOL);
    fwrite($out_handle, strtoupper($word) . $counter . PHP_EOL);
    $out_lines += 3;

    // both sides
    fwrite($out_handle, $counter . $word . $counter . PHP_EOL);
    fwrite($out_handle, $counter . ucwords($word) . $counter . PHP_EOL);
    fwrite($out_handle, $counter . strtoupper($word) . $counter . PHP_EOL);
    $out_lines += 3;

    // special
    fwrite($out_handle, $counter . $word . '34' . PHP_EOL);
    fwrite($out_handle, $counter . $word . '34!' . PHP_EOL);
    fwrite($out_handle, $counter . $word . '18' . PHP_EOL);
    fwrite($out_handle, $counter . $word . '18!' . PHP_EOL);
    $out_lines++;

    $counter++;
  }
  $lines_written += $out_lines;
}
lg(sprintf('Done, base list took %d seconds', time() - $start));

if ($do_rainbow_case) {
  lg('Outputting Rainbow Words');
  $start = time();
  foreach ($rainbow_words as $rainbow_word) {
    printf("Writing %s \r", $rainbow_word);

    $out_lines = 0;
    $counter = $config_min_num;

    // plain
    fwrite($out_handle, $rainbow_word . PHP_EOL);
    $out_lines++;

    while ($counter <= $config_max_num) {

      fwrite($out_handle, $counter . $word . PHP_EOL);
      fwrite($out_handle, $word . $counter . PHP_EOL);
      fwrite($out_handle, $counter . $word . $counter . PHP_EOL);
      $out_lines += 3;

      // special
      fwrite($out_handle, $counter . $rainbow_word . '34' . PHP_EOL);
      $out_lines++;

      $counter++;
    }
    $lines_written += $out_lines;
  }
  lg(sprintf('Done, Rainbow list took %d seconds', time() - $start));
}

fclose($out_handle);

$shell_file = [
  '#!/usr/bin/env bash',
  sprintf('john --wordlist=./dictionaries/%s --format=nt2 passwords.dump', $dict_file_name),
  sprintf('#john --wordlist=./dictionaries/%s --format=nt-opencl passwords.dump', $dict_file_name),
];

file_put_contents(ROOT. DIRECTORY_SEPARATOR . 'rip.sh', implode(PHP_EOL, $shell_file));

// 4. Profit!
lg(sprintf('Wrote a dictionary of ~%d words in %d seconds', $lines_written, time() - $g_start));

function lg($message, $with_eol = true)
{
  echo $message;
  if ($with_eol) {
    echo PHP_EOL;
  }
}
