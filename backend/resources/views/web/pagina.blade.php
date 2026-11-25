{{--
  Texto de la página para Google y las IA, dentro de #app (ver HtmlPublicoService).
  Vue lo sustituye al arrancar por la página con su diseño: aquí solo cuenta que sea HTML
  semántico (h1, h2, párrafos, enlaces) con el mismo texto que la vista.
--}}
<div class="mx-auto max-w-5xl px-4 py-10">
  <nav aria-label="Páginas de la web">
    <ul class="flex flex-wrap gap-4 text-sm">
      @foreach ($navegacion as $enlace)
        <li><a href="{{ $enlace['url'] }}">{{ $enlace['texto'] }}</a></li>
      @endforeach
    </ul>
  </nav>

  <main class="mt-8 space-y-10">
    @if ($entrada)
      <article>
        @if ($entrada->categoria)<p>{{ $entrada->categoria->nombre }}</p>@endif
        <h1 class="text-3xl font-bold">{{ $entrada->titulo }}</h1>
        @if ($entrada->publicada_en)
          <p><time datetime="{{ $entrada->publicada_en->toDateString() }}">{{ $entrada->publicada_en->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</time></p>
        @endif
        @if ($entrada->extracto)<p>{{ $entrada->extracto }}</p>@endif
        @if ($imagen)<img src="{{ $imagen }}" alt="{{ $entrada->titulo }}" class="max-w-full" />@endif
        {{-- Ya purificado al guardar (EntradaService, HTMLPurifier) --}}
        <div>{!! $entrada->contenido !!}</div>
      </article>
    @elseif (! $bloques)
      @if ($titulo)<h1 class="text-3xl font-bold">{{ $titulo }}</h1>@endif
    @endif

    @foreach ($bloques as $bloque)
      <section>
        @if ($bloque['antetitulo'])<p>{{ $bloque['antetitulo'] }}</p>@endif
        @if ($bloque['titulo'])
          @if ($bloque['nivel'] === 1)
            <h1 class="text-3xl font-bold">{{ $bloque['titulo'] }}</h1>
          @else
            <h2 class="text-2xl font-bold">{{ $bloque['titulo'] }}</h2>
          @endif
        @endif
        @foreach ($bloque['textos'] as $texto)
          <p>{{ $texto }}</p>
        @endforeach
        @foreach ($bloque['items'] as $item)
          <div>
            @if (isset($item['titulo']) || isset($item['cifra']))
              <h3 class="font-bold">{{ trim(($item['cifra'] ?? '').' '.($item['titulo'] ?? '')) }}</h3>
            @endif
            @isset($item['texto'])<p>{{ $item['texto'] }}</p>@endisset
            @isset($item['puntos'])
              <ul>@foreach ($item['puntos'] as $punto)<li>{{ $punto }}</li>@endforeach</ul>
            @endisset
          </div>
        @endforeach
        @if ($bloque['cifras'])
          <ul>
            @foreach ($bloque['cifras'] as $cifra)<li>{{ $cifra }}</li>@endforeach
          </ul>
        @endif
        @if ($bloque['enlaces'])
          <p class="flex flex-wrap gap-4">
            @foreach ($bloque['enlaces'] as $enlace)<a href="{{ $enlace['url'] }}">{{ $enlace['texto'] }}</a>@endforeach
          </p>
        @endif
      </section>
    @endforeach

    @if ($noticias->isNotEmpty())
      <section>
        <h2 class="text-2xl font-bold">Últimas noticias</h2>
        @foreach ($noticias as $noticia)
          <article>
            <h3><a href="/noticias/{{ $noticia->slug }}">{{ $noticia->titulo }}</a></h3>
            @if ($noticia->publicada_en)<p><time datetime="{{ $noticia->publicada_en->toDateString() }}">{{ $noticia->publicada_en->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</time></p>@endif
            @if ($noticia->extracto)<p>{{ $noticia->extracto }}</p>@endif
          </article>
        @endforeach
      </section>
    @endif

    @if ($contacto)
      <section>
        <h2 class="text-2xl font-bold">Datos de contacto</h2>
        <ul>
          @isset($contacto['email'])<li>Correo: <a href="mailto:{{ $contacto['email'] }}">{{ $contacto['email'] }}</a></li>@endisset
          @isset($contacto['telefono'])<li>Teléfono: {{ $contacto['telefono'] }}</li>@endisset
          @isset($contacto['direccion'])<li>Dirección: {{ $contacto['direccion'] }}</li>@endisset
        </ul>
      </section>
    @endif
  </main>
</div>
