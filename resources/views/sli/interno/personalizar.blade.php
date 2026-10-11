@extends('layouts.app')
@section('title', 'Editar sitio público')
@section('content')
<div class="sli-page-heading"><div><span class="sli-eyebrow">ADMINISTRACIÓN DEL DESPACHO</span><h1>Edita tu sitio.</h1><p>Cambia textos, colores e imágenes desde aquí. No necesitas modificar código ni abrir la base de datos.</p></div><a class="sli-button sli-button-outline" href="{{ route('home') }}" target="_blank" rel="noopener">Ver sitio público ↗</a></div>
@if(session('success'))<div class="sli-editor-notice" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="sli-editor-error" role="alert"><strong>Revisa estos campos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(!config('filesystems.disks.sli-assets.key'))<div class="sli-editor-notice">Almacenamiento de imágenes aún no conectado. Los demás cambios están disponibles.</div>@endif
<section class="sli-panel sli-editor-section"><header class="sli-panel-head"><div><h2>Portada, Conócenos y colores</h2><p>Elige los textos y colores que verán tus visitantes antes de iniciar sesión.</p></div></header>
<form method="POST" action="{{ route('interno.personalizar.update') }}" enctype="multipart/form-data" class="sli-editor-form">@csrf @method('PUT')
<fieldset><legend>Portada</legend>
<label>Antetítulo<input name="home[eyebrow]" value="{{ old('home.eyebrow', $content['home']['eyebrow']) }}" maxlength="90" required></label>
<label>Título<input name="home[title]" value="{{ old('home.title', $content['home']['title']) }}" maxlength="130" required></label>
<label>Descripción<textarea name="home[description]" maxlength="320" required>{{ old('home.description', $content['home']['description']) }}</textarea></label>
<label>Logotipo <span>JPG, PNG o WebP; máximo 2 MB.</span><input type="file" name="logo_image" accept="image/jpeg,image/png,image/webp"></label>
@if($content['home']['logo'])<div class="sli-editor-preview"><img src="{{ route('sli.media', $content['home']['logo']) }}" alt="Logotipo actual"><label><input type="checkbox" name="remove_logo_image" value="1"> Usar logotipo predeterminado</label></div>@endif
<label>Imagen de portada <span>JPG, PNG o WebP; máximo 4 MB.</span><input type="file" name="home_image" accept="image/jpeg,image/png,image/webp"></label>
@if($content['home']['image'])<div class="sli-editor-preview"><img src="{{ route('sli.media', $content['home']['image']) }}" alt="Imagen actual de portada"><label><input type="checkbox" name="remove_home_image" value="1"> Usar retrato predeterminado</label></div>@endif
</fieldset>
<fieldset><legend>Sección Conócenos</legend>
<label>Antetítulo<input name="about[eyebrow]" value="{{ old('about.eyebrow', $content['about']['eyebrow']) }}" maxlength="100" required></label>
<label>Título principal<input name="about[title]" value="{{ old('about.title', $content['about']['title']) }}" maxlength="130" required></label>
<label>Presentación del despacho<input name="about[summary]" value="{{ old('about.summary', $content['about']['summary']) }}" maxlength="240" required></label>
<label>Encabezado de sección<input name="about[heading]" value="{{ old('about.heading', $content['about']['heading']) }}" maxlength="130" required></label>
<label>Párrafo uno<textarea name="about[paragraph_one]" maxlength="600" required>{{ old('about.paragraph_one', $content['about']['paragraph_one']) }}</textarea></label>
<label>Párrafo dos<textarea name="about[paragraph_two]" maxlength="600" required>{{ old('about.paragraph_two', $content['about']['paragraph_two']) }}</textarea></label>
</fieldset>
<fieldset><legend>Colores del sitio público</legend><div class="sli-editor-colors">@foreach(['navy'=>'Azul marino','gold'=>'Dorado','canvas'=>'Fondo claro','sage'=>'Fondo secundario'] as $key=>$label)<label>{{ $label }}<input type="color" name="theme[{{ $key }}]" value="{{ old('theme.'.$key, $content['theme'][$key]) }}"></label>@endforeach</div></fieldset>
<button class="sli-button sli-button-primary" type="submit">Guardar cambios</button>
</form></section>

<section class="sli-panel sli-editor-section"><header class="sli-panel-head"><div><h2>Perfiles de abogados</h2><p>El directorio público muestra solo los abogados activos. Aquí puedes completar nombre, especialidad, biografía e imagen.</p></div></header>
<div class="sli-editor-lawyers">@forelse($abogados as $abogado)
<form method="POST" action="{{ route('interno.personalizar.abogado', $abogado->id) }}" enctype="multipart/form-data" class="sli-editor-lawyer">@csrf @method('PUT')
<div class="sli-editor-lawyer-head">@if($abogado->perfil_imagen)<img src="{{ route('sli.media', $abogado->perfil_imagen) }}" alt="" class="sli-editor-avatar">@else<div class="sli-lawyer-monogram sli-editor-avatar" aria-hidden="true">{{ mb_substr($abogado->nombre,0,1) }}</div>@endif<div><strong>{{ $abogado->nombre }}</strong><span>{{ $abogado->activo ? 'Perfil público activo' : 'Inactivo; no aparece al público' }}</span></div></div>
<label>Nombre<input name="nombre" value="{{ old('nombre', $abogado->nombre) }}" maxlength="120" required></label>
<label>Especialidad<input name="especialidad" value="{{ old('especialidad', $abogado->especialidad) }}" maxlength="120"></label>
<label>Biografía profesional<textarea name="perfil_biografia" maxlength="1600" placeholder="Formación, experiencia y áreas de práctica">{{ old('perfil_biografia', $abogado->perfil_biografia) }}</textarea></label>
<label>Foto profesional <span>JPG, PNG o WebP; máximo 4 MB.</span><input type="file" name="perfil_imagen" accept="image/jpeg,image/png,image/webp"></label>
<button class="sli-button sli-button-outline" type="submit">Guardar perfil</button>
</form>
@empty<div class="sli-public-empty"><h2>Aún no hay abogados registrados</h2><p>Cuando el despacho agregue abogados, podrás editar sus perfiles aquí.</p></div>@endforelse</div>
@if($abogados->hasPages())<div class="sli-public-pagination">{{ $abogados->links() }}</div>@endif
</section>
@endsection
