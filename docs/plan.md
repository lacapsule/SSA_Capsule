```c
                  ┌──────────────────────────┐
        Client →  │   HTTP Request (GET /..) │
                  └──────────────┬───────────┘
                                 │
                                 ▼
                           [ Server ]
                    builds Request::fromGlobals()
                                 │
                                 ▼
                             [ Kernel ]
                                 │
                                 ▼
       ┌──────────────────────── Pipeline ────────────────────────┐
       │  Request ↓                                                │
       │   ┌──────────┐   ┌──────────┐   ┌──────────┐              │
       │   │Middleware│ → │Middleware│ → │Middleware│ → …           │
       │   └──────────┘   └──────────┘   └──────────┘              │
       │         │                         │                       │
       │         └── can short-circuit with Response ──────────────┘
       │
       │
       ▼
      [ Router ] ----> match (path + method) ----> {route, params}
           │
           ▼
 [ ControllerResolver ] → instantiate + call Controller::action
           │
           ▼
       [ Controller ]
           │
           ├──→ [ Composants utilitaires ] (option)
           │
           ▼
       [ Services ]
           │
           ▼
      [ Repository ]
           │
           ▼
       creates Response (S0)

       Response ↑ flows back through Middlewares
       (headers added, timing, etc.)

                                 │
                                 ▼
                             [ Kernel ]
                                 │
                                 ▼
                           send Response
                                 │
                                 ▼
                  ┌──────────────────────────┐
        Client ←  │   HTTP Response (200 OK) │
                  └──────────────────────────┘

   [ ErrorHandler ] intercepts any Throwable at any stage
        → builds Response (404 / 500 / 405 + Allow)
``` 
