@extends('layouts.main')

@section('title', 'home')
@section('stylesheet', 'css/home.css')

@section('body')
    <form action="transferProducts">
        <h1>Get Data</h1>
        <input type="submit" value="Go">
    </form>
@endsection