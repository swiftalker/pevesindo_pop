# This file is responsible for configuring your application
# and its dependencies with the aid of the Config module.
#
# This configuration file is loaded before any dependency and
# is restricted to this project.

# General application configuration
import Config

config :center, :scopes,
  user: [
    default: true,
    module: Center.Accounts.Scope,
    assign_key: :current_scope,
    access_path: [:user, :id],
    schema_key: :user_id,
    schema_type: :id,
    schema_table: :users,
    test_data_fixture: Center.AccountsFixtures,
    test_setup_helper: :register_and_log_in_user
  ]

config :center,
  ecto_repos: [Center.Repo],
  generators: [timestamp_type: :utc_datetime],
  ash_domains: [
    Center.Inventory,
    Center.Procurement,
    Center.Sales,
    Center.MasterData,
    Center.Catalog,
    Center.Config
  ]

# ex_money / ex_cldr
config :ex_money, default_cldr_backend: Center.Cldr

# AshMoney — tipe :money di resource Ash + operator di ekspresi
# https://hexdocs.pm/ash_money/
config :ash, :known_types, [AshMoney.Types.Money]
config :ash, :custom_types, money: AshMoney.Types.Money

# Flop pagination
config :flop, repo: Center.Repo

# Odoo connection (overridden in runtime.exs with env vars)
config :center, Center.Integration.Odoo,
  url: "http://localhost:8069",
  database: "pevesindo",
  api_key: nil

# Oban background job processing
config :center, Oban,
  engine: Oban.Engines.Basic,
  repo: Center.Repo,
  plugins: [{Oban.Plugins.Cron, []}],
  queues: [
    default: 10,
    sync: 5,
    odoo: 5,
    chat_responses: [limit: 10],
    conversations: [limit: 10],
    # AshOban scheduled action queues
    warehouse_sync_from_odoo: 1,
    location_sync_from_odoo: 1,
    stock_quant_sync_from_odoo: 1,
    stock_move_line_sync_from_odoo: 1,
    product_sync_from_odoo: 1,
    customer_sync_from_odoo: 1
  ]

# Configure the endpoint
config :center, CenterWeb.Endpoint,
  url: [host: "localhost"],
  adapter: Bandit.PhoenixAdapter,
  render_errors: [
    formats: [html: CenterWeb.ErrorHTML, json: CenterWeb.ErrorJSON],
    layout: false
  ],
  pubsub_server: Center.PubSub,
  live_view: [signing_salt: "WIZhCcPm"]

# Configure the mailer
#
# By default it uses the "Local" adapter which stores the emails
# locally. You can see the emails in your browser, at "/dev/mailbox".
#
# For production it's recommended to configure a different adapter
# at the `config/runtime.exs`.
config :center, Center.Mailer, adapter: Swoosh.Adapters.Local

# Configure esbuild (the version is required)
config :esbuild,
  version: "0.25.4",
  center: [
    args: ~w(js/app.js --bundle --target=es2022 --outdir=../priv/static/assets/js --external:/fonts/* --external:/images/* --alias:@=.),
    cd: Path.expand("../assets", __DIR__),
    env: %{"NODE_PATH" => [Path.expand("../deps", __DIR__), Mix.Project.build_path()]}
  ]

# Configure tailwind (the version is required)
config :tailwind,
  version: "4.1.12",
  center: [
    args: ~w(
      --input=assets/css/app.css
      --output=priv/static/assets/css/app.css
    ),
    cd: Path.expand("..", __DIR__)
  ]

# Configure Elixir's Logger
config :logger, :default_formatter,
  format: "$time $metadata[$level] $message\n",
  metadata: [:request_id]

# Use Jason for JSON parsing in Phoenix
config :phoenix, :json_library, Jason

if config_env() in [:dev, :test] do
  import_config ".env.exs"
end

# Import environment specific config. This must remain at the bottom
# of this file so it overrides the configuration defined above.
import_config "#{config_env()}.exs"
