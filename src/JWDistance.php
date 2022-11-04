<?php

namespace alviden\JaroWinkerCorrector;

/**
 * Сходство Джаро - Винклера
 * Помимо расчета сходства есть исправление раскладки клавиатуры в случае, если исходное слово и вводимое отличаются алфавитами
 */
class JWDistance
{
    /**
     * Коэффициент масштабирования. Принимает значения не более 0.25. По стандарту 0.1
     */
    private float $coefP = 0.1;

    /**
     * Исправление раскладки клавиатуры. Например, ключ - en раскладка, значение - ру раскладка
     * В случае, если исправление раскладки не требуется, необходимо передать пустой массив
     * Ключом должен идти алфавит, числовое представление которого меньше
     * Проверить можно функцией mb_ord()
     */
    private array $puntoMap = [
        'a' => 'ф', 'b' => 'и', 'c' => 'с', 'd' => 'в', 'e' => 'у', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'i' => 'ш', 'j' => 'о', 'k' => 'л', 'l' => 'д', 'm' => 'ь',
        'n' => 'т', 'o' => 'щ', 'p' => 'з', 'q' => 'й', 'r' => 'к', 's' => 'ы', 't' => 'е', 'u' => 'г', 'v' => 'м', 'w' => 'ц', 'x' => 'ч', 'y' => 'н', 'z' => 'я',
        '[' => 'х', ']' => 'ъ', ';' => 'ж', '\'' => 'э', ',' => 'б', '.' => 'ю'
    ];

    /**
     * Самый "старший" ключ, используется для проверки необходимости в исправлении раскладки клавиатуры
     * @var string
     */
    private string $maxPuntoChar = 'z';

    /**
     * сравнивает 2 строки, на выходе получаем вес
     * Чем выше вес, тем больше вероятность, что $str1 необходимо заменить на $str2
     * @param string $str1 слово, для исправления
     * @param string $str2 корректное искомое слово из словаря
     */
    public function distance(string $str1, string $str2): float
    {
        if (!empty($this->puntoMap)) {
            $firstSymbolStr1 = mb_substr($str1, 0, 1);
            $firstSymbolStr2 = mb_substr($str2, 0, 1);
            //если вводимое слово и искомое слово в разных раскладках, вводимое слово приводим к искомому
            if (
                ($firstSymbolStr1 <= $this->maxPuntoChar && $firstSymbolStr2 > $this->maxPuntoChar)
                || ($firstSymbolStr2 <= $this->maxPuntoChar && $firstSymbolStr1 > $this->maxPuntoChar)
            ) {
                $str1 = $this->puntoSwitcher($str1);
            }
        }
        //узнаем длину строк, чтобы затем правильно сравнить (вторая строка должна быть длиннее первой)
        $s1 = mb_strlen($str1);
        $s2 = mb_strlen($str2);
        if ($s1 < $s2) {
            $str1 = preg_split('//u', ($str1), -1, PREG_SPLIT_NO_EMPTY);
            $str2 = preg_split('//u', ($str2), -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $tempStr1 = $str1;
            $str1     = preg_split('//u', ($str2), -1, PREG_SPLIT_NO_EMPTY);
            $str2     = preg_split('//u', ($tempStr1), -1, PREG_SPLIT_NO_EMPTY);
        }

        $l = 0; //кол-во первых совпадающих символов (не более 4)
        $m = 0; //кол-во совпадающих символов
        $t = 0; //число транзакций

        foreach ($str1 as $key => $symb) {
            if ($symb == $str2[$key]) {
                $m++;
                if ($key < 4) {
                    $l++;
                }
            } elseif (isset($str2[$key - 1]) && $symb == $str2[$key - 1] || isset($str2[$key + 1]) && $symb == $str2[$key + 1]) {
                $m++;
                $t++;
            }
        }
        $p = $this->coefP;
        $t /= 2;
        //расстояние Джаро
        $dj = $m ? 1/3 * ($m/$s1 + $m/$s2 + ($m-$t)/$m) : 0;
        //расстояния Джаро-Винклера
        $dw = $dj + ($l * $p * (1-$dj));

        return $dw;
    }

    /**
     * Исправим "раскладку клавиатуры" для строки
     * Например, <b>ghbdtn -> привет</b> и наоборот
     * @param string $str
     * @return string
     */
    public function puntoSwitcher(string $str): string
    {
        //разобьем слово по буквам
        $strArr = preg_split('//u', (mb_strtolower($str)), -1, PREG_SPLIT_NO_EMPTY);
        $res    = '';
        //пройдем по массиву и попробуем заменить буквы на ключ из $puntoMap или значение
        foreach ($strArr as $symb) {
            $res .= $this->puntoMap[$symb] ?? (array_search($symb, $this->puntoMap) ?: null) ?? $symb;
        }

        return $res;
    }

    /**
     * get $coefP
     * @return float
     */
    public function getCoefP(): float
    {
        return $this->coefP;
    }

    /**
     * set $coefP
     * @param float $coefP
     * @return $this
     */
    public function setCoefP(float $coefP): self
    {
        if ($coefP >= 0 && $coefP <= 0.25) {
            $this->coefP = $coefP;
        }

        return $this;
    }

    /**
     * get $puntoMap
     * @return string[]
     */
    public function getPuntoMap(): array
    {
        return $this->puntoMap;
    }

    /**
     * set $puntoMap
     * @param array $puntoMap
     * @return $this
     */
    public function setPuntoMap(array $puntoMap): self
    {
        $this->puntoMap = $puntoMap;
        if (!empty($puntoMap)) {
            $this->maxPuntoChar = max(array_keys($puntoMap));
        }

        return $this;
    }
}
