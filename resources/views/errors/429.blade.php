@extends('errors.layout')
@php
    $status = 429;
    $title = 'Too Many Requests';
    $message = "You've made too many requests. Please slow down and try again later.";
@endphp
