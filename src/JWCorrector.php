<?php

namespace alviden\JaroWinkerCorrector;

use Closure;

/**
 * Класс, корректирующий орфографию и раскладку
 * Может использоваться, например, при поисковых запросах
 */
class JWCorrector
{
    /**
     * Минимальный вес, при котором слово будет исправляться на ближайшее найденное.
     * Чем больше значение, тем ниже вероятность, что слово будет исправлено
     * Принимает значения от 0 до 1
     */
    private float $jwMinWeight = 0.8;

    /**
     * Разрядность карты хешей. Следует использовать значение, равное степени двойки
     * (8, 16, 32 итд)
     * Зависит от значений, указанных в hashMap
     * (максимальный элемент минус минимальный элемент)
     */
    private int $quantityRanks = 16;

    /**
     * Карта, по которой собираются хеши.
     * Ключи - символы, которые кодируются в определенные разряды
     * Значения - позиция разрядов
     * Например, пустое слово кодируется хешем с нулями - <i>0000000000000000</i>
     * Если в слове есть буква "А" - то нулевой элемент заменится на единицу - <i>1000000000000000</i>
     * Если есть буква "М", то и десятый элемент заменится на единицу - <i>1000000001000000</i>
     * Этот хеш будет соответствовать словам "Ма", "Ам", "Мама" итд
     */
    private array $hashMap = [
        'а' => 0, 'о' => 0, 'f' => 0, 'j' => 0,
        'б' => 1, 'п' => 1, ',' => 1, 'g' => 1,
        'в' => 2, 'ф' => 2, 'd' => 2, 'a' => 2,
        'г' => 3, 'к' => 3, 'х' => 3, 'u' => 3, 'r' => 3, '[' => 3,
        'д' => 4, 'т' => 4, 'l' => 4, 'n' => 4,
        'е' => 5, 'ё' => 5, 'э' => 5, 't' => 5, '`' => 5, '\''=> 5,
        'ж' => 6, 'ш' => 6, 'щ' => 6, ';' => 6, 'i' => 6, 'o' => 6,
        'з' => 7, 'с' => 7, 'p' => 7, 'c' => 7,
        'и' => 8, 'ы' => 8, 'й' => 8, 'b' => 8, 's' => 8, 'q' => 8,
        'л' => 9, 'р' => 9, 'k' => 9, 'h' => 9,
        'м' => 10, 'н' => 10, 'v' => 10, 'y' => 10,
        'у' => 11, 'ю' => 11, 'e' => 11, '.' => 11,
        'ц' => 12, 'ч' => 12, 'w' => 12, 'x' => 12,
        'ь' => 13, 'ъ' => 13, 'm' => 13, ']' => 13,
        'z' => 14, 'я' => 14,
    ];

    /**
     * Слова, подходящие по хешу
     */
    private array $words;

    /**
     * Возвращает нормализованное слово
     * @param string $str введенное пользователем слово
     * @return string нормализованное слово
     */
    public function getCorrectWord(string $str): string
    {
        $distanceObj = new JWDistance();
        $maxCoef  = 0;
        $needName = $str;
        foreach ($this->words as $word) {
            $tempCoef = $distanceObj->distance($str, $word);
            if ($maxCoef < $tempCoef) {
                $maxCoef  = $tempCoef;
                $needName = $word;
            }
        }

        return ($maxCoef > $this->jwMinWeight) ? $needName : $str;
    }

    /**
     * хеширование по сигнатуре. На вход получает строку, на выходе хэш
     * @param string $str
     * @return string
     */
    public function sign(string $str): string
    {
        $strArr       = preg_split('//u', ($str), -1, PREG_SPLIT_NO_EMPTY);
        $resHashTable = array_fill(0, $this->quantityRanks, 0); //заполняем массив нулями

        foreach ($strArr as $symb) {
            if (isset($this->hashMap[$symb])) {
                $resHashTable[$this->hashMap[$symb]] = 1;
            }
        }

        return implode('', $resHashTable);
    }

    /**
     * Получение всех хешей, похожих на строку $str
     * @param string $str
     * @param \Closure $function анонимная функция, которая на вход получает строку хеша, а на выходе - массив похожих слов
     * @return $this
     */
    public function setWordsByClosure(string $str, Closure $function): self
    {
        $hash = $this->sign($str);
        $hashArr = preg_split('//u', $hash, -1, PREG_SPLIT_NO_EMPTY);

        $resArr = [];
        foreach ($hashArr as $key => $oneHash) {
            $newHashArr = $hashArr;
            $newHashArr[$key] = $newHashArr[$key] ? 0 : 1;
            $resArr = array_merge($resArr, $function(implode('', $newHashArr)));
        }
        $this->words = $resArr;

        return $this;
    }

    /**
     * get $jwMinWeight
     * @return float
     */
    public function getMinWeight(): float
    {
        return $this->jwMinWeight;
    }

    /**
     * set $jwMinWeight
     * @param float $jwMinWeight
     * @return $this
     */
    public function setMinWeight(float $jwMinWeight): self
    {
        if ($jwMinWeight > 0 && $jwMinWeight < 1) {
            $this->jwMinWeight = $jwMinWeight;
        }

        return $this;
    }

    /**
     * get $words
     * @return array
     */
    public function getWords(): array
    {
        return $this->words;
    }

    /**
     * set $words
     * @param array $words
     * @return $this
     */
    public function setWords(array $words): self
    {
        $this->words = $words;

        return $this;
    }

    /**
     * get $quantityRanks
     * @return int
     */
    public function getQuantityRanks(): int
    {
        return $this->quantityRanks;
    }

    /**
     * get $hashMap
     * @return int[]
     */
    public function getHashMap(): array
    {
        return $this->hashMap;
    }

    /**
     * set $hashMap
     * @param array $hashMap
     * @return $this
     */
    public function setHashMap(array $hashMap): self
    {
        $countElems = count($hashMap);
        if ($countElems > 0) {
            $this->hashMap       = $hashMap;
            $this->quantityRanks = $countElems;
        }

        return $this;
    }

}
