<script setup>
const year = new Date().getFullYear()

// Update these with the business's real details/handles before launch.
const contact = {
  email: 'hello@mystyle.co.ke',
  phone: '+254 700 000 000',
}

const socials = [
  { name: 'Instagram', href: 'https://instagram.com/salonbook', icon: 'instagram' },
  { name: 'Facebook', href: 'https://facebook.com/salonbook', icon: 'facebook' },
  { name: 'X', href: 'https://x.com/salonbook', icon: 'x' },
  { name: 'TikTok', href: 'https://tiktok.com/@salonbook', icon: 'tiktok' },
]
</script>

<template>
  <footer class="border-t border-ink/10 bg-white mt-12">
    <div class="max-w-5xl mx-auto px-4 py-8 grid sm:grid-cols-3 gap-6">
      <div>
        <div class="flex items-center gap-2 mb-2">
          <img src="/logo.jpg" alt="SalonBook" class="w-7 h-7 rounded-full object-cover" />
          <span class="font-heading font-semibold text-ink">MyStyle</span>
        </div>
        <p class="text-sm text-ink-soft">Find and book barbers, salons, and nail artists near you.</p>
      </div>

      <div>
        <h3 class="text-sm font-medium text-ink mb-2">Contact</h3>
        <a :href="`mailto:${contact.email}`" class="block text-sm text-ink-soft hover:text-clay-600">{{ contact.email }}</a>
        <a :href="`tel:${contact.phone.replace(/\s/g, '')}`" class="block text-sm text-ink-soft hover:text-clay-600 mt-1">{{ contact.phone }}</a>
      </div>

      <div>
        <h3 class="text-sm font-medium text-ink mb-2">Follow us</h3>
        <div class="flex items-center gap-3">
          <a v-for="s in socials" :key="s.name" :href="s.href" target="_blank" rel="noopener"
            :aria-label="s.name" class="text-ink-soft hover:text-clay-600 w-8 h-8">
            <svg v-if="s.icon === 'instagram'" viewBox="0 0 24 24" fill="none" class="w-full h-full">
              <rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.6" />
              <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.6" />
              <circle cx="17.2" cy="6.8" r="1" fill="currentColor" />
            </svg>
            <svg v-else-if="s.icon === 'facebook'" viewBox="0 0 24 24" fill="none" class="w-full h-full">
              <path d="M15 8h2V5h-2c-2.2 0-4 1.8-4 4v2H9v3h2v6h3v-6h2.2l.8-3H14V9c0-.6.4-1 1-1z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
            </svg>
            <svg v-else-if="s.icon === 'x'" viewBox="0 0 24 24" fill="none" class="w-full h-full">
              <path d="M5 5l14 14M19 5L5 19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" class="w-full h-full">
              <path d="M15 4v9.5a3.5 3.5 0 1 1-3.5-3.5c.3 0 .6 0 .9.1V7.8a6 6 0 1 0 5.1 5.9V9.6a6.3 6.3 0 0 0 3.5 1.1V7.4A4 4 0 0 1 15 4z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
            </svg>
          </a>
        </div>
      </div>
    </div>

    <div class="border-t border-ink/10 py-4">
      <p class="text-center text-xs text-ink-soft/70">© {{ year }} MyStyle. All rights reserved.</p>
    </div>
  </footer>
</template>
