# PHP Tail Stream

PHP implementation of the Linux `tail` command (display the last part of a file), optimized for reading huge size files
with effective memory usage.

## Installation

You can install the package via Composer:

```bash
composer require jakubboucek/php-tail-stream
```

and declare namespace:
```php
use JakubBoucek\Tail\Tail;
```

## Usage

```php
Tail::lines(10)->fromFile('path/to/your/file.txt')->toOutput();

// Output the last 10 lines of the file
```

You can also read data from your stream:
    
```php

$f = fopen('path/to/your/file.txt', 'rb');
Tail::lines(10)->fromStream($f)->toOutput();

// Output the last 10 lines from the stream
```
    
> [!NOTE]  
> Source stream MUST allow seeking! You can't use some stream types, like HTTP stream for example.

You can write result to file or another stream, use methods:

```php
 ->toFile('path/to/your/output.txt'); // Write to file
 ->toStream($stream);                 // Write to stream
 ->toIterator();                      // Get iterator containting lines as strings
```

> [!WARNING]  
> The `->toIterator()` method reads the every line to memory. Use it carefully - when you load huge file with very long
> lines, it can cause memory issues. Be aware especially when you load unknown files, when file doesn't have line
> separators (e.g. if you accidentally load binary file), whole file will be loaded to memory. To prevent unexpected
> behavior is method limited to 1MB of data. You can change this limit by passing the argument `$maxLineSize`.


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
