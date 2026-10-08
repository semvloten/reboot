{{--
Onderdeel: Foutpagina bij een actie die door een gewijzigde of gereserveerde apparaatstatus niet mogelijk is.
Eisen: FE-07, FE-12, RV-07.
Bouw: T-13 (keuring), T-22 (reserveren), T-34 (foutafhandeling).
Geplande controle: T-14, T-23, T-33.
--}}

@extends('errors::minimal')

@section('code', '409')
