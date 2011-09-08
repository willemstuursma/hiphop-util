<?php
/**
 * Applies the callback to the elements of the given arrays, but uses multiple
 * threads.
 *
 * @param callback $function You can use a classic php callback, e.g. a string
 * or array pointing to a function. Passing in an anonymous function will not
 * work, Hiphop does not allow them as arguments to call_user_func_async().
 * @param array $array
 */
function array_map_parallel($function, array $array) {

    if (($array_length = count($array)) === 0) {
        // In case of empty array, return immediately.
        return $array;
    }

    // Do not create more threads than there are items in the array
    // max threads is three (main thread + 2x call_user_func_async()).
    $num_threads = min($array_length, 3);

    if ($num_threads === 1 || !function_exists('call_user_func_async')) {
        // Don't bother using threads.
        return array_map($function, $array);
    }

    $chunks = array_chunk($array, floor($array_length / $num_threads));

    $workers = array();

    // Start all workers
    for ($i = 1; $i < count($chunks); ++$i) {
        // @ supresses HPHP's deprecated warning.
        $workers[$i] = @call_user_func_async('array_map', $function, $chunks[$i]);
    }

    // also do it yourself once.
    $return = array_map($function, $chunks[0]);

    // Wait for all workers, this blocks
    for ($i = 1 ; $i < $num_threads; ++$i) {
        $return = array_merge($return, end_user_func_async($workers[$i]));
    }

    return $return;
}

