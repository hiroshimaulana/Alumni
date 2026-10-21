<?php
/**
 * Istanbul University MIS Alumni Portal
 * JobPosting Factory
 *
 * Generates realistic employment opportunity data for Istanbul University
 * Management Information Systems alumni and corporate partners.
 */

class JobPostingFactory {
    private static array $companies = [
        ['name' => 'Trendyol Group', 'id' => 101, 'location' => 'Istanbul, Turkey (Hybrid)'],
        ['name' => 'Garanti BBVA Technology', 'id' => 102, 'location' => 'Istanbul, Turkey'],
        ['name' => 'Amazon Web Services', 'id' => 103, 'location' => 'Berlin, Germany / Remote'],
        ['name' => 'Getir', 'id' => 104, 'location' => 'Istanbul, Turkey / Amsterdam, NL'],
        ['name' => 'Turkcell Technology', 'id' => 105, 'location' => 'Istanbul, Turkey (Maltepe Plaza)'],
        ['name' => 'Hepsiburada Tech', 'id' => 106, 'location' => 'Istanbul, Turkey (Şişli HQ)'],
        ['name' => 'Eczacıbaşı Holding IT', 'id' => 107, 'location' => 'Istanbul, Turkey (Kanyon)'],
        ['name' => 'Baykar Tech', 'id' => 108, 'location' => 'Istanbul, Turkey (Özdemir Bayraktar Center)'],
    ];

    private static array $titles = [
        [
            'title' => 'Junior Cloud Data Engineer',
            'type' => 'full_time',
            'level' => 'entry',
            'salary' => '₺55,000 - ₺70,000 / month',
            'desc' => 'Join the Big Data Platform squad to design scalable distributed data pipelines using Apache Spark, Kafka, and Google Cloud BigQuery.',
            'req' => 'Python, SQL, Apache Spark or Airflow, strong foundation in relational and distributed database modeling.'
        ],
        [
            'title' => 'MIS Business Systems Analyst',
            'type' => 'full_time',
            'level' => 'mid',
            'salary' => '₺60,000 - ₺80,000 / month',
            'desc' => 'Bridge banking business stakeholders with agile development teams, drafting detailed user stories, BPMN workflow models, and acceptance criteria.',
            'req' => 'Agile/Scrum ceremonies, UML/BPMN, SQL query optimization, Business Process Re-engineering (BPR).'
        ],
        [
            'title' => 'Cloud Solutions Architect Intern',
            'type' => 'internship',
            'level' => 'entry',
            'salary' => '€2,200 / month',
            'desc' => 'Direct mentorship from IU MIS alumni working on enterprise Well-Architected Framework reviews, serverless architectures, and Kubernetes clusters.',
            'req' => 'Linux shell scripting, TCP/IP fundamentals, AWS Cloud Practitioner or equivalent cloud certification enthusiasm.'
        ],
        [
            'title' => 'ERP & SAP S/4HANA Consultant',
            'type' => 'full_time',
            'level' => 'mid',
            'salary' => '₺65,000 - ₺85,000 / month',
            'desc' => 'Lead digital transformation initiatives across supply chain and financial accounting modules within SAP S/4HANA enterprise installations.',
            'req' => 'SAP SD/MM or FI/CO configuration knowledge, relational database architecture, business consulting acumen.'
        ],
        [
            'title' => 'Full-Stack Web Developer (PHP / Laravel / Vue)',
            'type' => 'full_time',
            'level' => 'mid',
            'salary' => '₺55,000 - ₺75,000 / month',
            'desc' => 'Develop secure, responsive web applications and REST APIs for alumni and student engagement portals.',
            'req' => 'PHP 8+, modern JavaScript, HTML5/CSS3, SQL, Docker, Git workflow.'
        ],
        [
            'title' => 'Information Security & Compliance Specialist',
            'type' => 'full_time',
            'level' => 'senior',
            'salary' => '₺80,000 - ₺110,000 / month',
            'desc' => 'Oversee ISO 27001 compliance, GDPR / KVKK data protection audits, vulnerability scans, and disaster recovery testing.',
            'req' => 'KVKK / GDPR regulation literacy, SIEM systems, pen-testing awareness, CISSP or CISA certification.'
        ]
    ];

    /**
     * Create a realistic job posting array.
     */
    public static function definition(int $index = 0, array $overrides = []): array {
        $company = self::$companies[$index % count(self::$companies)];
        $titleInfo = self::$titles[$index % count(self::$titles)];

        $default = [
            'posted_by_user_id' => 1 + ($index % 3), // Posters are users 1, 2, or 3 (alumni/faculty)
            'company_name'      => $company['name'],
            'company_id'        => $company['id'],
            'title'             => $titleInfo['title'],
            'description'       => $titleInfo['desc'],
            'employment_type'   => $titleInfo['type'],
            'location'          => $company['location'],
            'salary_range'      => $titleInfo['salary'],
            'experience_level'  => $titleInfo['level'],
            'status'            => 'active',
            'requirements'      => $titleInfo['req'],
            'deadline'          => date('Y-m-d', strtotime('+' . (30 + ($index * 15)) . ' days')),
        ];

        return array_merge($default, $overrides);
    }

    /**
     * Generate an array of multiple job posting definitions.
     */
    public static function count(int $num): array {
        $postings = [];
        for ($i = 0; $i < $num; $i++) {
            $postings[] = self::definition($i);
        }
        return $postings;
    }
}
