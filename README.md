Модуль поиска на основе сходства Джаро — Винклера
=============================

Расширение, помогающее исправлять пользовательские опечатки и раскладку клавиатуры.
Может использоваться для исправления поисковых запросов
без использования других различных поисковых движков.
Пакет включает в себя реализацию сходства JaroWinkler,
PuntoSwitcher и хеширование по сигнатуре

УСТАНОВКА
------------

Предпочтительно для установки использовать [composer](http://getcomposer.org/download/).

```
php composer.phar require --prefer-dist alviden/jaro-winkler "*"
```

ИСПОЛЬЗОВАНИЕ
------------
Для расчета расстояния Джаро-Винклера:
```
	$jw = new JWDistance();
	$jw->distance('слово1', 'слово2');
```
Для поиска среди набора фраз (слов)
```
        $corrector = new JWCorrector();
        $corrector->setWords([
            'хлебцы',
            'хлеб',
            'клей',
            'клев',
            'хлев',
            'глеб',
        ]);
        echo $corrector->getCorrectWord('хлебы'); //хлеб
        echo $corrector->getCorrectWord('хлебц'); //хлебцы
        echo $corrector->getCorrectWord('ukt,'); //глеб (была изменена раскладка клавиатуры)
```
Для поиска среди большого количества фраз (слов) по хешу
```
        $corrector = new JWCorrector();
        $arr = [
            'большое',
            'количество',
            'слов',
            'которое',
            'хранятся',
            'например',
            'в БД',
            'хлебцы',
            'хлеб',
            'клей',
            'клев',
            'хлев',
            'глеб',
        ];
        //$hashMap, условно, в данном случае - отдельная таблица слов и их хешей
        $hashMap = [];
        foreach ($arr as $word) {
            $hashMap[] = [
                'hash' => $corrector->sign($word),
                'word' => $word,
            ];
        }
        //$f - анонимная функция, которую будем передавать для ограничения извлекаемых данных
        $f = function($hash) use ($hashMap) {
            $resArr = [];
            foreach ($hashMap as $elem) {
                if ($elem['hash'] == $hash) {
                    $resArr[] = $elem['word'];
                }
            }
            return $resArr;
        };
        $corrector->setWordsByClosure('хле', $f);
        var_dump($corrector->getWords());
        /* array(5) {
          [0]=>
          string(8) "хлеб"
          [1]=>
          string(8) "глеб"
          [2]=>
          string(8) "клев"
          [3]=>
          string(8) "хлев"
          [4]=>
          string(8) "клей"
        }
        */
```
