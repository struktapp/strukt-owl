Strukt Owl
===

AI package. Mostly text analysis. More to come...

<!-- ![Won](owl.jpg "Owl") -->

<center><img src="owl.jpg" width="200" height="200"></center>

## Usage

### Sentiment Analysis: Vader Score

```php
$text = "From Oasis' first ever single to the name of Noel and Liam's long-suffering mother, just how well do YOU know the band? Take MailOnline's ultimate quiz";

probe($text);
```

### Tags: Ranking

```php

// use tags function
tags($text); //get all tags

// get to 10 tags above 0.9 ranking
arr(tags($text))->each(fn($k,$v)=>number($v)->gt(0.9)?$k:null)->filter()->values()->yield()
```

### Summarize

```php
$text = fs()->cat("news.txt");

summary($text);
```

### Highlights

```php
$text = fs()->cat("news.txt");

highlights($text);
```

## Credits

- TextRank https://github.com/davmixcool/php-sentiment-analyzer
- PHP Sentiment Analyzer https://github.com/DavidBelicza/PHP-Science-TextRank

