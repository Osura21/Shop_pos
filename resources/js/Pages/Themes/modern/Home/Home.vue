<template>
    <MultiVendorLayout>
  <section class="modern-hero">
    <!-- Background Video/Image -->
    <div class="hero-background">
      <video 
        autoplay 
        muted 
        loop 
        playsinline 
        class="hero-video"
        poster="/assets/images/hero-fallback-image.png" onloadeddata="this.classList.add('video-loaded')">
      >
        <source src="/assets/videos/car-background.mp4" type="video/mp4">
      </video>
      <div class="hero-overlay"></div>
      <div class="hero-gradient"></div>
    </div>

    <!-- Content -->
    <div class="hero-container">
      <div class="hero-content">
        <!-- Badge -->
        <div class="hero-badge">
          <span>TRUSTED BY 10K+ CUSTOMERS</span>
        </div>
        
        <!-- Headline -->
        <h1 class="hero-headline">
          <span class="hero-headline-primary">Find Your</span>
          <span class="hero-headline-accent">Dream Car</span>
          <span class="hero-headline-secondary">Today</span>
        </h1>
        
        <!-- Subtitle -->
        <p class="hero-subtitle">
          Discover premium vehicles from verified sellers. 
          Buy, sell, or trade with complete confidence.
        </p>
        
        <!-- Search -->
        <div class="hero-search">
          <div class="search-container">
            <div class="search-icon">
              <SearchIcon />
            </div>
            <input 
              type="text" 
              class="search-input"
              placeholder="Search for make, model, or keyword..."
              v-model="searchQuery"
              @focus="isSearchFocused = true"
              @blur="isSearchFocused = false"
            >
            <button class="search-button">
              <span>Search</span>
              <ArrowRight />
            </button>
          </div>
          <div class="search-tags">
            <span class="tag">Tesla</span>
            <span class="tag">BMW</span>
            <span class="tag">Mercedes</span>
            <span class="tag">Audi</span>
            <span class="tag">SUVs</span>
          </div>
        </div>
        
        <!-- Stats -->
        <div class="hero-stats">
          <div class="stat">
            <div class="stat-number">10K+</div>
            <div class="stat-label">Vehicles</div>
          </div>
          <div class="stat-divider"></div>
          <div class="stat">
            <div class="stat-number">98%</div>
            <div class="stat-label">Satisfaction</div>
          </div>
          <div class="stat-divider"></div>
          <div class="stat">
            <div class="stat-number">24h</div>
            <div class="stat-label">Delivery</div>
          </div>
        </div>
      </div>
      
    </div>
  </section>

        <!-- <FilterSection /> -->
        <!-- <BrowseSection /> -->
        <FeaturedListings />
        <SellCTA />
        <TrustIndicators />

    </MultiVendorLayout>
</template>

<script>
// import BrowseSection from "./Components/BrowseSection.vue";
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue";
import FeaturedListings from "./Components/FeauturedListings.vue";
import SellCTA from "./Components/SellCTA.vue";
import TrustIndicators from "./Components/TrustIndicators.vue";
// import FilterSection from "./Components/FilterSection.vue";
import { usePage } from "@inertiajs/vue3"
import { SearchIcon, ArrowRight } from 'lucide-vue-next'

export default {
    name: "Home",

    components: {
        MultiVendorLayout,
        // BrowseSection,
        FeaturedListings,
        SellCTA,
        TrustIndicators,
        // FilterSection
    },

    data() {
        return {
            placeholderText: "",
            phrases: [
                "Search For Vehicle Models..."
            ],

            phraseIndex: 0,
            charIndex: 0,
            mode: "typing",
            holdCounter: 0,
            typeSpeed: 120,
            deleteSpeed: 80,
            holdTime: 20,

            intervalId: null,
            isPaused: false
        };
    },

    mounted() {
        const page = usePage()
  console.log("Home page props:", page.props)
        setTimeout(this.startTypingLoop, 300);
    },

    beforeUnmount() {
        clearInterval(this.intervalId);
    },

    methods: {
        startTypingLoop() {
            if (this.intervalId) return;

            this.intervalId = setInterval(() => {
                if (this.isPaused) return;

                const phrase = this.phrases[this.phraseIndex];

                if (this.mode === "typing") {
                    this.charIndex++;
                    this.placeholderText = phrase.substring(0, this.charIndex);

                    if (this.charIndex === phrase.length) {
                        this.mode = "holding";
                        this.holdCounter = 0;
                    }
                }

                else if (this.mode === "holding") {
                    this.holdCounter++;

                    if (this.holdCounter >= this.holdTime) {
                        this.mode = "deleting";
                    }
                }

                else if (this.mode === "deleting") {
                    this.charIndex--;
                    this.placeholderText = phrase.substring(0, this.charIndex);

                    if (this.charIndex === 0) {
                        this.mode = "typing";
                        this.phraseIndex =
                            (this.phraseIndex + 1) % this.phrases.length;
                    }
                }
            }, this.mode === "deleting" ? this.deleteSpeed : this.typeSpeed);
        },

        pauseTyping() {
            this.isPaused = true;
        },

        resumeTyping() {
            this.isPaused = false;
        },
    }
};
</script>




<style scoped>
.modern-hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
  padding-top: 80px;
}

.hero-background {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.hero-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.6);
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(24, 59, 78, 0.85) 0%,     
    rgba(39, 84, 138, 0.5) 50%,     
    rgba(24, 59, 78, 0.85) 100%
  );
}

/* Gradient accent */
.hero-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    rgba(221, 168, 83, 0.15) 0%,    
    transparent 50%,
    rgba(39, 84, 138, 0.15) 100%    
  );
}

.hero-container {
  position: relative;
  z-index: 2;
  max-width: 1440px;
  margin: 0 auto;
  padding: 2rem;
  width: 100%;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.hero-badge {
  display: inline-block;
  background: rgba(221, 168, 83, 0.15); 
  backdrop-filter: blur(10px);
  border: 1px solid rgba(221, 168, 83, 0.3);
  border-radius: 50px;
  padding: 0.5rem 1.5rem;
  margin-bottom: 3rem;
}

.hero-badge span {
  color: #FFFFFF;
  font-size: 0.875rem;
  font-weight: 500;
  letter-spacing: 1px;
}

.hero-headline {
  font-size: clamp(3rem, 8vw, 5rem);
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 1.5rem;
  color: white;
}

.hero-headline-primary {
  display: block;
  opacity: 0.9;
}

.hero-headline-accent {
  display: block;
  background: linear-gradient(90deg, #DDA853, #C19A4F); 
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-headline-secondary {
  display: block;
  opacity: 0.9;
}

.hero-subtitle {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 1); 
  max-width: 600px;
  margin: 0 auto 3rem;
  line-height: 1.6;
}

/* Search container */
.search-container {
  position: relative;
  max-width: 700px;
  margin: 0 auto 1.5rem;
  display: flex;
  background: #FFFFFF; 
  backdrop-filter: blur(20px);
  border-radius: 100px;
  border: 2px solid transparent;
  transition: all 0.3s ease;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(24, 59, 78, 0.15); 
}

.search-container:focus-within {
  border-color: #DDA853;
  box-shadow: 0 20px 40px rgba(221, 168, 83, 0.2); 
  transform: translateY(-2px);
}

.search-icon {
  display: flex;
  align-items: center;
  padding: 0 1.5rem;
  color: #183B4E; 
}

.search-input {
  flex: 1;
  padding: 1.2rem 0;
  border: none;
  background: transparent;
  font-size: 1.1rem;
  color: #183B4E; 
  outline: none;
}

.search-input::placeholder {
  color: #8AA4BE; 
}

.search-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 2rem;
  background: linear-gradient(135deg, #183B4E, #27548A); 
  color: #FFFFFF;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.search-button:hover {
  gap: 1rem;
  padding-right: 2.5rem;
  background: linear-gradient(135deg, #DDA853, #C19A4F); 
  color: #183B4E;
}

.search-input {
  flex: 1;
  padding: 1.2rem 0;
  border: none;
  background: transparent;
  font-size: 1.1rem;
  color: #1f2937;
  outline: none;
}

.search-input::placeholder {
  color: #9ca3af;
}

.search-tags {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.tag {
  padding: 0.5rem 1rem;
  background: rgba(221, 168, 83, 0.15); 
  border: 1px solid rgba(221, 168, 83, 0.3);
  border-radius: 50px;
  color: #FFFFFF;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.tag:hover {
  background: #DDA853; 
  color: #183B4E;
}

.hero-stats {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 3rem;
  margin-top: 4rem;
}

.stat {
  text-align: center;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: 700;
  color: #DDA853; 
  line-height: 1;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: rgba(221, 168, 83, 0.3); 
}

.hero-scroll {
  position: absolute;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.scroll-line {
  width: 1px;
  height: 60px;
  background: linear-gradient(
    to bottom,
    transparent,
    rgba(221, 168, 83, 0.6),
    transparent
  );
  animation: scrollLine 2s infinite;
}

.scroll-text {
  color: rgba(221, 168, 83, 0.8); 
  font-size: 0.875rem;
  letter-spacing: 2px;
  text-transform: uppercase;
}

@keyframes scrollLine {
  0%, 100% {
    opacity: 0.3;
  }
  50% {
    opacity: 1;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .hero-container {
    padding: 1rem;
  }
  
  .hero-badge {
    margin-bottom: 2rem;
    font-size: 0.75rem;
  }
  
  .hero-stats {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .stat-divider {
    width: 60px;
    height: 1px;
  }
  
  .search-container {
    flex-direction: column;
    border-radius: 20px;
  }
  
  .search-button {
    padding: 1rem;
    justify-content: center;
  }
}
section{
  margin-bottom: 3rem;
}
</style>
