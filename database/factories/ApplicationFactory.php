<?php
/**
 * Istanbul University MIS Alumni Portal
 * Application Factory
 *
 * Generates realistic student/alumni job application submissions.
 */

class ApplicationFactory {
    private static array $applicants = [
        [
            'name' => 'Ayşe Öztürk',
            'email' => 'student.ayse@ogr.iu.edu.tr',
            'user_id' => 4,
            'github' => 'https://github.com/ayseozturk',
            'letter' => 'I am an IU MIS senior student specialized in Data Engineering with hands-on coursework in Big Data systems and cloud analytics.'
        ],
        [
            'name' => 'Can Aydın',
            'email' => 'student.can@ogr.iu.edu.tr',
            'user_id' => 5,
            'github' => 'https://linkedin.com/in/canaydin',
            'letter' => 'Passionate about FinTech and Enterprise ERP systems. Looking forward to contributing to digital banking architecture.'
        ],
        [
            'name' => 'Deniz Kurt',
            'email' => 'deniz.kurt@ogr.iu.edu.tr',
            'user_id' => 2,
            'github' => 'https://github.com/denizkurt-mis',
            'letter' => 'Graduating MIS student with strong full-stack web and API development background, eager to join an agile engineering team.'
        ],
    ];

    public static function definition(int $index = 0, array $overrides = []): array {
        $applicant = self::$applicants[$index % count(self::$applicants)];
        $statuses = ['submitted', 'under_review', 'shortlisted', 'accepted'];

        $default = [
            'job_posting_id'  => 1 + ($index % 3),
            'user_id'         => $applicant['user_id'],
            'applicant_name'  => $applicant['name'],
            'applicant_email' => $applicant['email'],
            'resume_url'      => '/uploads/resumes/' . strtolower(str_replace(' ', '_', $applicant['name'])) . '_cv.pdf',
            'cover_letter'    => $applicant['letter'],
            'portfolio_url'   => $applicant['github'],
            'status'          => $statuses[$index % count($statuses)],
            'feedback'        => 'Applicant evaluated favorably during initial academic portfolio screening.'
        ];

        return array_merge($default, $overrides);
    }
}
