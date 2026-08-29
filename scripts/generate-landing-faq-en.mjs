import fs from 'fs';
import { faqs, why, platformCards, testimonials, steps, nav, footer, brand } from '../resources/js/content/landing.js';

const landingEn = {
  brand: { name: brand.name },
  header: { signIn: 'Sign in', startFree: 'Start for free', menu: 'Menu' },
  hero: {
    beta: 'BOOSTDZ is now in public beta',
    learnMore: 'Learn more',
    titleLine1: 'Grow Your',
    titleLine2: 'Real Likes, Real Followers.',
    subtitle:
      'Buy real likes, followers, and views for Instagram, TikTok, YouTube, and X — with instant delivery and no password required. Genuine engagement that helps every post travel further.',
    getStarted: 'Get Started',
    learnMoreCta: 'Learn More',
    asSeenOn: 'As seen on',
    asSeenNote: "Yes, it's not fake, we really did get featured",
  },
  preview: {
    dashboard: 'Dashboard',
    dashboardDesc: 'Overview of your growth',
    orders: 'Orders',
    ordersDesc: 'Manage your orders',
    automation: 'Automation',
    automationDesc: 'Set rules and let it run',
  },
  why: {
    title: 'Why Creators Pick BOOSTDZ Over Everyone Else',
    subtitle:
      'Everything you need to grow your social media — instant delivery, real engagement, drip-feed delivery, and automatic refill, across every platform.',
    growHighlight: 'grow your social media',
    items: why,
  },
  platforms: {
    title: 'Real Hearts, Fans and Plays, Delivered Fast',
    subtitle:
      'Buy real likes, followers, and views for Instagram, TikTok, YouTube, X, and Facebook. Pick your network, choose your service, start growing today.',
    platformsHighlight: 'Instagram, TikTok, YouTube, X, and Facebook',
    cards: platformCards,
  },
  testimonials: {
    title: 'Hear It From Creators, Brands and Agencies',
    subtitle:
      'See how creators, businesses, and agencies use BOOSTDZ for real social proof and measurable social media growth.',
    likesDelivered: 'Likes delivered this month',
    satisfaction: 'Customer satisfaction rate',
    leaveReviewTitle: 'Love BOOSTDZ? Leave a review',
    leaveReviewBody:
      'Tell other creators what worked for you. Your review helps the community pick the right tools.',
    leaveReviewCta: 'Leave a Review',
    tapToPlay: 'Tap to play · swipe for more',
    items: testimonials,
  },
  nav,
  steps,
  footer: {
    ...footer,
    sections: { links: 'Links', products: 'Featured Products', tools: 'Free Tools', comparisons: 'Comparisons' },
    about:
      'Powering social media growth. BOOSTDZ serves creators, agencies, and brands with engagement services across Instagram, TikTok, YouTube, and X. Based in Algeria.',
    rights: 'All rights reserved.',
    terms: 'Terms of Service',
    refund: 'Refund Policy',
    privacy: 'Privacy Policy',
  },
};

fs.writeFileSync('resources/js/i18n/locales/en/landing.json', JSON.stringify(landingEn, null, 2));
fs.writeFileSync('resources/js/i18n/locales/en/faq.json', JSON.stringify({ groups: faqs }, null, 2));
console.log('en landing+faq written');
