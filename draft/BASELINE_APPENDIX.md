# **Appendix: Mapping Stack Elixir → Laravel**

Referensi cepat untuk developer yang membaca baseline original (Elixir/Phoenix/Ash).

| **Baseline Original (Elixir)** | **Laravel Equivalent**      | **Status**             |
| ------------------------------ | --------------------------- | ---------------------- |
| Phoenix LiveView               | Livewire 3                  | ✅ Hampir 1:1          |
| Phoenix PubSub                 | Laravel Reverb + Echo       | ✅ Full replacement    |
| Ash Framework                  | Eloquent ORM                | ✅ More familiar       |
| ash_state_machine              | spatie/laravel-model-states | ✅ Mature & tested     |
| ash_paper_trail                | owen-it/laravel-auditing    | ✅ Drop-in             |
| ash_archival                   | SoftDeletes (bawaan)        | ✅ Native              |
| ash_authentication             | Laravel Fortify/Breeze      | ✅ Native              |
| Oban + oban_web                | Laravel Horizon + Queue     | ✅ Full replacement    |
| ash_events                     | Laravel Events & Listeners  | ✅ Native, lebih mudah |
| ash_admin                      | FilamentPHP 3               | ✅ Lebih powerful      |
| ash_money                      | brick/money                 | ✅ Equivalent          |
| ash_csv                        | spatie/simple-excel         | ✅ Equivalent          |
| PostgreSQL                     | PostgreSQL                  | ✅ Sama persis         |

**📝 Living Document:** Dokumen ini adalah living document. Setiap penambahan cabang, role, atau workflow baru akan diappend mengikuti struktur dan format yang telah ditetapkan di sini.
