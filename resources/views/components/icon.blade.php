@props(['name'])
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('camera')<path d="M4 7h4l2-3h4l2 3h4v13H4z"/><circle cx="12" cy="13" r="4"/>@break
@case('shield')<path d="m12 3 8 3v6c0 5-8 9-8 9S4 17 4 12V6l8-3Z"/><path d="m8 12 3 3 5-6"/>@break
@case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>@break
@case('heart')<path d="M20.5 4.7a5.5 5.5 0 0 0-7.8 0L12 5.4l-.7-.7a5.5 5.5 0 0 0-7.8 7.8L12 21l8.5-8.5a5.5 5.5 0 0 0 0-7.8Z"/>@break
@case('home')<path d="m3 10 9-7 9 7M5 9v12h5v-7h4v7h5V9"/>@break
@case('edit')<path d="m15 5 4 4M4 20l5-1L21 7a2.8 2.8 0 0 0-4-4L5 15l-1 5Z"/>@break
@case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>@break
@case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>@break
@case('menu')<path d="M4 7h16M4 12h16M4 17h16"/>@break
@case('arrow')<path d="M4 12h16m-6-6 6 6-6 6"/>@break
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
@case('car')<path d="m5 10 2-6h10l2 6M3 10h18v8H3zM5 18v3m14-3v3M3 14h4m10 0h4"/>@break
@case('parts')<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M12 4v5m8 3h-5m-3 8v-5m-8-3h5"/>@break
@case('laptop')<rect x="5" y="3" width="14" height="13" rx="1"/><path d="m5 16-3 5h20l-3-5M10 18h4"/>@break
@case('garden')<path d="M12 21V10M12 15C4 15 3 8 4 4c7 0 9 4 8 11Zm0-4c0-6 5-8 9-8 0 7-3 9-9 9"/>@break
@case('fish')<path d="M3 12c4-8 11-8 15-2l4-3v10l-4-3C14 20 7 20 3 12Z"/><circle cx="8" cy="11" r=".7"/><path d="m11 5 3-3 2 5m-5 12 3 3 2-5"/>@break
@case('lamp')<path d="M8 3h8l5 11H3L8 3Zm4 11v7m-5 0h10"/>@break
@case('clothes')<path d="m8 3-5 3-2 6 5 2v7h12v-7l5-2-2-6-5-3c0 4-8 4-8 0Z"/>@break
@case('bike')<circle cx="5" cy="17" r="4"/><circle cx="19" cy="17" r="4"/><path d="m5 17 5-10 5 10H5m9-13h3l2 13M8 7h5"/>@break

@endswitch
</svg>
