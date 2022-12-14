# Test #1
1. После клонирования проекта с GitHub, перейдите в папку с проектом и через консоль запустите следующую команду
> composer install
2. Для наполнения БД первичными данными запустите команду
> php getCars.php
3. Как только скрипт закончит работу в папке проекта появится папка с загруженными фотографиями, а БД будет наполнена данными об автомобилях. Тем самым я выполнил задание 1.1 + дополнительное задание.
4. Для получения автомобилей из задания 1.2, необходимо запустить команду
> php showCars.php
5. Наслаждайтесь полученными данными.

# Test #2
На данный вопрос я отвечал в самом конце технического собеседования.
Но, давайте дам более развернутый ответ здесь. Суть алгоритма, который предлагаю я, в следующем. 
Из ID автомобиля мы получаем md5 хэш. Так как md5 хэш имеет 128 бит = 16 байт = 32 символа, мы можем разбить данный ключ на блоки с четной длиной и создать из них дерево папок.
Данный метод избавит вас от совершенно ненужного хранения "связей" в БД и всегда позволит найти путь до папки с фото конкретного авто очень быстро (намного быстрее, чем получать эти данные из БД где будут миллионы записей).

### Пример
- md5(123) = 202cb962ac59075b964b07152d234b70
- Разбиваем хэм на блоки длиной 2 символа. Получаем путь "20/2c/b9/62/ac/59/07/5b/96/4b/07/15/2d/23/4b/70"

Уникальность необратимого хэша позволит создать нам уникальное дерево, которое позволить хранить очень большое количество информации, не опасаясь за то, что мы исчерпаем максимально допустимое число файлов в одной папке. 
Да и диски будет очень долго обрабатывать информацию если мы будем обращаться в файлам лежащим в папке, где будут сотни тысяч других файлов.
Алгоритм, который я предлагаю, имеет строгие математические ограничения.

### Немного математики
Необратимый хэш состоит только из цифр и букв нижнего регистра.
Давайте посчитаем, сколько максимум папок на блок мы получим.
Длина блока = 2 символа. Эти два символа у нас могут состоять из 10 цифр + 26 букв.
(10 + 26) ^ 2 = 1296 - такое максимальное количество папок на блок, мы получим в пиковом случае.

### PS
Скрипт написанный Test #1 и Test #2 как раз наглядно демонстрирует работу данного алгоритма. 
