<?php
/**
*	Предок всех исключений, связанных с HTTP - кодами.
*   Нужен для того чтобы все, кому есть дело до исключений,
*   могли легко определить исключения HTTP и их обрабатывать/не обрабатывать.
*/
class HttpException extends Exception {
}