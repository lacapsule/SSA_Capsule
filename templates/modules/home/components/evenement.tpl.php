<section class="evenement section" id="evenement">
    <div class="contain">
        <div class="title">
            <div class="section-title">
                <h2>évènements</h2>
                <p>{{str.agenda_intro}}</p>
            </div>
        </div>

        <div class="evenement row">
            {{^articles}}
            <p class="no-event">{{str.no_upcoming_articles}}</p>
            {{/articles}}
            {{#each articles}}
            <div class="evenement-item ">
                <div class="evenement-item-inner shadow-dark">
                    <div class="evenement-info info">
                        <div class="evenement-date">
                            <p>{{date_event}}</p>
                        </div>
                        <div class="evenement-time">
                            <p>{{time}}</p>
                        </div>
                    </div>
                    <div class="evenement-info desc">
                        <h4 class="evenement-title">{{title}}</h4>
                        <p class="evenement-description">{{summary}}</p>
                    </div>
                </div>
            </div>
            {{/each}}
        </div>
    </div>
</section>
