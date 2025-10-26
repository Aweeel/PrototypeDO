<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = "Student Handbook";
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>STI Discipline Office - <?php echo htmlspecialchars($pageTitle); ?></title>

  <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Ensure tailwind uses class-based dark mode
        tailwind.config = {
            darkMode: 'class'
        }

        // Restore saved theme on page load
        if (localStorage.getItem("theme") === "dark") {
            document.documentElement.classList.add("dark");
        }

        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle("dark");
            localStorage.setItem("theme", isDark ? "dark" : "light");
            
        }
    </script>

<style>
  html { scroll-behavior: smooth; }

  /* Make section scroll offset account for fixed header */
  [id] {
    scroll-margin-top: 6rem; /* Adjust if your header height changes */
  }

  /* Slim, subtle scrollbar styling */
  .custom-scrollbar::-webkit-scrollbar {
    width: 6px;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 3px;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: rgba(96, 165, 250, 0.8);
  }

  /* Highlight animation for active TOC link */
  .active-toc-link {
    color: #2563eb !important; /* Tailwind blue-600 */
    font-weight: 600;
  }
  .dark .active-toc-link {
    color: #60a5fa !important; /* lighter blue for dark mode */
  }
</style>

</head>

<body class="bg-gray-50 dark:bg-[#1E293B] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="flex">
  <div class="flex-1 ml-64 w-full min-h-screen overflow-x-hidden">
      <?php include __DIR__ . '/../../includes/header.php'; ?>

      <main class="p-8 pt-28 min-h-screen transition-colors duration-300 flex gap-10">
        <!-- ==== MAIN CONTENT ==== -->
        <div class="flex-1 max-w-9xl ">
          <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm p-20">
            <h2 class="text-5xl font-bold mb-6 text-gray-800 dark:text-gray-100">
              <?php echo htmlspecialchars($pageTitle); ?>
            </h2>

           <!-- ================= GENERAL INFORMATION ================= -->
<section id="general-info" class="space-y-6 text-justify text-lg">
  <h3 class="text-3xl font-semibold mb-4">GENERAL INFORMATION</h3>

  <div id="sti-history">
    <h4 class="font-semibold text-2xl">STI History</h4>
    <p>
      <br>It all started when four visionaries conceptualized setting up a training center to fill very specific workforce needs.<br><br>
      It was in the early ‘80s when Augusto C. Lagman, Herman T. Gamboa, Benjamin A. Santos, and Edgar H. Sarte — four entrepreneurs and friends — came together to set up Systems Technology Institute, a training center that delivers basic programming education to professionals and students who want to learn this new skill.<br><br>
      Lagman, Gamboa, and Sarte were all heavily involved in the growing computer industry, while Santos had just retired from his IT position in a pharmaceutical company.<br><br>
      Sarte’s software house, Systems Resources Incorporated (SRI), kept losing programmers and analysts to jobs abroad. Programmers and analysts were a rare breed then, with only a few training centers offering courses on computer programming.<br><br>
      Therefore, there was a clear need to find and hire people for SRI and fulfill this need for a growing business industry that was migrating to automated or computerized business processes. The founders transformed the problem into an opportunity.<br><br>
      Systems Technology Institute’s name came from countless brainstorming sessions among the founders — perhaps from Sarte’s penchant for three-letter acronyms from the companies he managed at the time.<br><br>
      The first two schools were inaugurated on August 21, 1983 in Buendia, Makati and in España, Manila, and offered basic computer programming courses. With a unique and superior product in their hands, it was not difficult to expand the franchise through the founders’ business contacts. A year after the first two schools opened, the franchise grew to include STI Binondo, Cubao, and Taft.<br><br>
      A unique value proposition spelled the difference for the STI brand: “First We’ll Teach You, Then We’ll Hire You.” Through its unique Guaranteed Hire Program (GHP), all qualified graduates were offered jobs by one of the founders’ companies or through their contacts in the industry.<br><br>
      The school’s 1st batch of graduates, all 11 of them, were hired by SRI. Through the GHP, more qualified STI graduates found themselves working in their field of interest straight out of school.<br><br>
      No one among the four founders imagined that Systems Technology Institute would become a college or would grow to have 63 campuses with one university across the country. This can be attributed to the institution’s unique value proposition, the synergy between the founders and their personnel, and the management’s commitment to delivering quality education.<br><br>
      Moreover, after years of positioning itself as an IT school focused on providing high-quality education to the Filipino youth, STI slowly integrated itself into the education industry as a school that provides boundless career opportunities in non-IT programs such as Business and Management, Hospitality Management, Tourism Management, Engineering, Arts and Sciences, Maritime, and Criminal Justice Education.<br><br>
      With its wealth of experience in launching education programs needed by the market, STI also responded to the shift in the education landscape in 2013 by taking the lead in the country as the largest pioneer school to offer the Senior High School Program.
    </p>
  </div>

  <div id="sti-vision">
    <h4 class="font-semibold text-2xl">STI Vision</h4>
    <p><br>
      To be the leader in innovative and relevant education that nurtures individuals to become competent and responsible members of society.
    </p>
  </div>

  <div id="sti-mission">
    <h4 class="font-semibold text-2xl">STI Mission</h4>
    <p><br>
      We are an institution committed to provide knowledge through the development and delivery of superior learning systems.<br><br>
      We strive to provide optimum value to all our stakeholders — our students, our faculty members, our employees, our partners, our shareholders, and our community.<br><br>
      We will pursue this mission with utmost integrity, dedication, transparency, and creativity.
    </p>
  </div>

  <div id="sti-seal">
    <h4 class="font-semibold text-2xl">STI Academic Seal</h4>
    <p>
        <br>The STI Academic Seal is designed to signify the institution’s commitment to its vision and mission.<br><br>
    <div class="flex justify-center my-6">
        <div class="p-4 rounded-xl dark:bg-white/90 bg-transparent shadow-sm">
            <img src="../../assets/images/logos/Sti-Academic-Seal.png" alt="STI Academic Seal" class="w-40 h-40 object-contain"/>
        </div>
    </div>
        The seal embodies the academic character of the institution through the following four (4) elements: <br><br>
        • The <strong>laurel leaves</strong>, symbolizing academic excellence, emphasize STI’s commitment
            to provide every student with holistic development through technology-enhanced,
            student-centered active learning.
<br><br>            
        • The <strong>flame</strong>, symbolizing enlightenment, represents STI’s undying commitment and
            passion to transform its students to become lifelong learners.
<br><br>
            • The <strong>flame bearers</strong>, represented by the academic institution on one side and its
            student body on the other, exemplify the entire STI community united by a shared
            purpose of using their knowledge, skills, values, experience, and abilities for the
            benefit of society.
<br><br>
            • The Latin inscription <strong>“Vita Educationem”</strong> translates to “Life Education,” which
            captures the overall thrust of the institution to provide Education for Real Life.
    </p>
  </div>

  <div id="sti-philosophy">
    <h4 class="font-semibold text-3xl">STI Educational Philosophy</h4>
  </div>

  <div id="sti-way">
    <h4 class="font-semibold text-2xl">STI Way of Educating</h4>
    <p>
        <br>Having embraced the student-centered approach as its paradigm for teaching and learning, STI seeks to provide every student with a holistic development through technology-enhanced, student-centered active learning. <br><br>
        The STI Learning System strives to offer learning opportunities that allow students to maximize their potential and grow into intellectual, emotional, physical, and social maturity so that they will be able to thrive in a continuously changing, technology-driven world. <br><br>
        Student formation is also important in the STI way of educating its students. An STIer is further defined by the 4Cs — character, critical thinker, communicator, and change-adept. 
    </p>
  </div>

  <div id="character">
    <h4 class="font-semibold text-2xl">Character</h4>
    <p>
        <br>An STIer is a person of character. An STIer takes responsibility for their actions, treats people with respect, and lives with integrity. 
    </p>
  </div>


  <div id="critical-thinker">
    <h4 class="font-semibold text-2xl">Critical Thinker</h4>
    <p>
       <br>An STIer is a critical thinker. An STIer challenges and analyzes all information through
            sound questioning and is unafraid to push for creative ideas. 
    </p>
  </div>

  <div id="communicator">
    <h4 class="font-semibold text-2xl">Communicator</h4>
    <p>
        <br>An STIer communicates to understand and be understood. 
        An STIer discerns the value of information read or heard and effectively expresses their own emotions when sharing information, may it be spoken or written.
    </p>
  </div>

  <div id="change-adept">
    <h4 class="font-semibold text-2xl">Change-Adept</h4>
    <p>
        <br>An STIer is change-adept. An STIer can adjust, adapt, and reinvent continuously to changing circumstances. 
        An STIer believes in letting go of the old and embracing the new to achieve their fullest potential.
    </p>
  </div>

  <div id="educational-goal">
    <h4 class="font-semibold text-2xl">Educational Goal</h4>
    <p>
        <br>The focus of STI is the student. The teaching and learning environment address the characteristics of an authentic STI student to enable them to achieve their maximum potential. <br><br>
        The goal is for the student to:<br><br>
        • feel safe, free from threats, distractions, and humiliations so they can learn; <br><br>
        • appreciate that what is being taught matters — the content/topic must concern the students and the real world; <br><br>
        • be active — learning experiences must be hands-on and collaborative;<br><br>
        • be stretched to another level — learning experiences can be hard but must be doable and take the student to a new level;<br><br>
        • have someone who can guide them — it is okay to make mistakes because it is part of the process; however, some tasks need guidance and feedback to make learning stick;<br><br>
        • use what was learned — the acquired knowledge and skills may stick to the learner if they are given the chance to recall, teach, and perform them;<br><br>
        • recall the lessons learned — the student must reflect on the things they learned and go back to the process of how they learned; and <br><br>
        • move forward, stretching himself/herself further by planning on their next steps 

    </p>
  </div>

  <div id="sti-network">
    <h4 class="font-semibold text-3xl">STI Educational Network System</h4>
    <p>
        <br>In its commitment to nurturing globally competitive individuals, STI continues to improve its system of providing real-life education. <br><br>
        To effectively extend its services, the STI network is composed of the following structures, each with its own specific functions and objectives:
    </p>
  </div>

  <div id="colleges">
    <h4 class="font-semibold text-2xl">The Colleges</h4>
    <p>
        <br>The STI Colleges provide a variety of programs in the fields of Information and
            Communications Technology (ICT), Engineering, Business and Management, Tourism
            Management, Hospitality Management, Arts and Sciences, Maritime, and Criminal Justice
            Education. Programs with associate, baccalaureate, and master’s degrees are duly
            authorized by the Commission on Higher Education (CHED), while the two-year programs
            are recognized by the Technical Education and Skills Development Authority (TESDA).
            Additionally, TESDA programs equip the graduates with TESDA Certifications and the option
            to continue further studies.
    </p>
  </div>

  <div id="education-centers">
    <h4 class="font-semibold text-2xl">The Education Centers</h4>
    <p>
        <br>The STI Education Centers provide three-year, two-year, one-year, and other short-term
technical vocational programs in the fields of Information and Communications Technology
(ICT), Hospitality Management, and Tourism Management. These programs are duly
authorized by the Technical Education and Skills Development Authority (TESDA). TESDA
programs equip the graduates with TESDA Certifications and the option to continue further
studies. These certifications provide them with opportunities for immediate entry-level
employment.
    </p>
  </div>

  <div id="senior-high">
    <h4 class="font-semibold text-2xl">The Senior High Schools</h4>
    <p>
        <br>The Senior High School (SHS) program, which covers Grades 11 and 12, provides a wide
range of academic and technical-vocational-livelihood tracks that are duly authorized by
the Department of Education (DepEd). With the knowledge imparted by certified faculty
members, training in state-of-the-art facilities, and STI’s unique learning supplements, STI
Senior High School graduates are well-equipped to go to college, seek employment, or start
their own businesses worldwide.
    </p>
  </div>

  <div id="junior-high">
    <h4 class="font-semibold text-2xl">The Junior High Schools</h4>
    <p>
        <br>The Junior High School (JHS) program ensures that Grades 7 to 10 students will experience
an enhanced, context-based, and spiral progression learning curriculum based on the
Department of Education’s requirements.
    </p>
  </div>
</section>

<!-- ================= ACADEMIC POLICIES & PROCEDURES ================= -->
<section id="academic-policies" class="space-y-6 text-justify text-lg mt-16">
  <h3 class="text-3xl font-semibold mb-4">ACADEMIC POLICIES & PROCEDURES</h3>

  <div id="school-student-relationship">
    <h4 class="font-semibold text-2xl">School-Student Relationship</h4>
    <p>
      <br>A student who submitted the admission requirements and is fully admitted to the school
has already entered into a legal contract with the school. The enrollment form is the first
contract that binds the student and the school. Both parties are expected to promote and
protect their mutual interests and fulfill their responsibilities and obligations as stated in
this handbook. Parents/guardians must also acquaint themselves with the content and
provisions in this handbook.
    </p>
  </div>

  <div id="admission-policy">
    <h4 class="font-semibold text-2xl">Admission Policy and Requirements</h4>
    <p>
      <br>STI welcomes all applicants belonging to any religious affiliation and nationality. The school,
however, has the right at any time to refuse to admit or re-admit students under certain
situations.<br><br>

The following requirements must be submitted to the Registrar’s Office before admission
to any academic program:</p>
  </div>

  <div id="incoming-freshmen">
    <h4 class="font-semibold text-2xl">Incoming Freshmen</h4><br>
    <ol type="1" class="list-decimal list-inside space-y-2">
      <li>Original copy of Form 138 or SF9-SHS (Original Copy of uncanceled Grade 12 Learner’s Progress Report Card)</li>
      <li>Original copy of Form 137 or SF10-SHS (Learner’s Permanent Academic Record)</li>
      <li>Philippine Statistics Authority (PSA) issued Birth Certificate</li>
      <li>Original copy of Certificate of Good Moral Character or Recommendation from the School Principal</li>
      <li>Medical certificate of chest X-ray results</li>
      <li>Medical certificate of Hepatitis A & B screening for BSHM, BSCM, HRA, DHRT, HRS, and HOP applicants</li>
      <li>Accomplished and signed Health Status and Acknowledgement of Disability Form</li>

    </ol>
  </div>

  <div id="transferees">
    <h4 class="font-semibold text-2xl">Transferees</h4><br>
     <ol type="1" class="list-decimal list-inside space-y-2">
      <li>Certificate of Transfer (Honorable Dismissal)</li>
      <li>Official Transcript of Records with remarks “Copy for STI College or For Enrollment Purposes Only”</li>
      <li>Philippine Statistics Authority (PSA) issued Birth Certificate</li>
      <li>Original copy of Certificate of Good Moral Character or Recommendation from the Dean/Program or Department Head</li>
      <li>Medical certificate of chest X-ray results</li>
      <li>Medical certificate of Hepatitis A & B screening for BSHM, BSCM, HRA, DHRT, HRS, and HOP applicants</li>
      <li>Accomplished and signed Health Status and Acknowledgement of Disability Form</li>
    </ol>
  </div>

  <div id="als">
    <h4 class="font-semibold text-2xl">Alternative Learning System Accreditation and Equivalency (ALS A&E) Passers</h4><br>
     <ol type="1" class="list-decimal list-inside space-y-2"> 
      <li>Result of the ALS A&E such as Certificate of Rating (COR), Learner’s Permanent Record (AF-5), or ALS Certificate of Program Completion, whichever is applicable.</li>
      <li>Original copy of Certificate of Good Moral Character or Recommendation from the School Principal/ALS Teacher/ALS Implementor/Learning Facilitator/ALS Focal Person or its equivalent (e.g., clearance from Barangay, Police, or NBI)</li>
      <li>Philippine Statistics Authority (PSA) issued Birth Certificate</li>
      <li>Medical certificate of chest X-ray results</li>
      <li>Medical certificate of Hepatitis A & B screening for BSHM, BSCM, HRA, DHRT, HRS, and HOP applicants</li>
      <li>Accomplished and signed Health Status and Acknowledgement of Disability Form</li>
    </ol>
  </div>

  <div id="foreign-students">
    <h4 class="font-semibold text-2xl">Students with Scholastic Records from a Foreign School</h4>
    <br>
     <ol type="1" class="list-decimal list-inside space-y-2">
      <li>Five (5) copies of the Student’s Personal History Statement (PHS) containing his/her left and right thumbprints and a 2” x 2” photograph on plain white background taken not more than six months prior to submission.</li>
      <li>Original Form 138/SF9 (Learner’s Progress Report Card) or its equivalent, and Original Form 137/SF10 (Learner’s Permanent Academic Record) or its equivalent authenticated by the Philippine Foreign Service Post (FSP), which has jurisdiction over the place of issuance, or by the Department of Foreign Affairs (DFA) if said document is issued by the local Embassy in the Philippines, with English translation if written in another foreign language.</li>
      <li>Notarized Affidavit of Support, including bank statements or notarized grant for institutional scholars.</li>
      <li>Photocopy of the student’s passport (valid for at least 6 months) showing date and place of birth.</li>
      <li>Photocopy of the Special Study Permit (SSP) for students below 18 years old or Student Visa for students 18 years old and above issued by the Bureau of Immigration (BI). These shall not be required of the spouses and unmarried dependent children below 21 years old of aliens under the following categories:
        <ul class="list-disc list-inside ml-5">
          <li>Permanent foreign residents</li>
          <li>Aliens with valid working permits under Section 9(d), 9(g) and 47(a) (2) of the Philippine Immigration Act of 1940, as amended</li>
          <li>Personnel of foreign diplomatic and consular missions residing in the Philippines and their dependents</li>
          <li>Personnel of duly accredited international organizations residing in the Philippines</li>
          <li>Holders of Special Investor’s Resident Visa (SIRV) and Special Retiree’s Resident Visa (SRRV)</li>
        </ul>
      </li>
      <li>Foreign students coming to the Philippines with 47(a) (2) visas issued pursuant to existing laws, e.g., Pres. Decree No. 2021.</li>
      <li>NBI or Police Clearance.</li>
      <li>Original copy of Certificate of Good Moral Character or Recommendation from the School Principal, Dean, Program or Department Head.</li>
      <li>Medical certificate of chest X-ray results.</li>
      <li>Medical certificate of Hepatitis A & B screening for BSHM, BSCM, HRA, DHRT, HRS, and HOP applicants.</li>
      <li>Accomplished and signed Health Status and Acknowledgement of Disability Form.</li>
    </ol>
    <p class="mt-2"><strong>Note:</strong> In the absence of a PSA-issued Birth Certificate, a birth certificate issued by the local civil registrar, local barangay, or National Statistics Office may be submitted instead.</p>
  </div>

  <div id="special-students">
    <h4 class="font-semibold text-2xl">Special (Non-Credit) Students</h4><br>
     <ol type="1" class="list-decimal list-inside space-y-2">
      <li>Letter of intent to study without credit</li>
      <li>Resumé that contains educational background</li>
      <li>Previous scholastic records</li>
    </ol>
  </div>

  <div id="disqualification">
    <h4 class="font-semibold text-2xl">Disqualification</h4>
        <p><br>An applicant may be disqualified for admission for any of the following reasons:</p>
    <ol type="1" class="list-decimal list-inside space-y-2">
        <li>Failure to submit the admission requirements</li>
        <li>Presentation of false documents</li>
    </ol>
  </div>

  <div id="residency">
    <h4 class="font-semibold text-2xl">Residency</h4>
    <p>
      <br>There is a prescribed minimum and maximum period of residency to acquire a degree or
educational program at STI.
</p>
  </div>

  <div id="minimum-residency">
    <h4 class="font-semibold text-2xl">Minimum Residency</h4>
    <p>
      <br>Since a graduate shall carry the name of STI, a minimum residency is prescribed to ensure
the quality of learning and immersion into the STI culture. A minimum residence for
graduation from the school is one (1) school year with a minimum total load of 30 units.

    </p>
  </div>

  <div id="maximum-residency">
    <h4 class="font-semibold text-2xl">Maximum Residency</h4>
    <p>
      <br>Programs offered at STI are designed based on the current needs of the industry.
Therefore, a student must finish the requirements of a program within a certain period to
ensure that the knowledge gained is applicable. Maximum residency should be equivalent
to one (1) school year or an additional 50% of the total number of years of the program,
whichever is higher.<br><br>

A student going beyond the maximum residency is subject to re-evaluation before readmission. The school reserves the right to refuse re-admission. Furthermore, the student
may be required to take refresher courses to ensure that their knowledge on the program
is up to date.
    </p>
  </div>

  <div id="leave-of-absence">
    <h4 class="font-semibold text-2xl">Leave of Absence (LOA)</h4>
    <p>
      <br>A student is expected to enroll in each term until they complete the program or is dropped
from the roll of the school. A student who plans to discontinue their studies temporarily
must formally file in writing for official Leave of Absence (LOA) with the Registrar’s Office
before the end of the term wherein the leave applies.<br><br>

The maximum leave period that may be applied for in a single application is one (1)
school year. However, the total duration of all leaves taken must not exceed the length
of the program. If the LOA is more than one (1) year, a student must follow the latest
curriculum by the time they return to school.
    </p>
  </div>

  <div id="extension-of-leave">
    <h4 class="font-semibold text-2xl">Extension of Leave</h4>
    <p>
      <br>Any extension of an official leave is considered another leave application. As such, the
leave extension must be formally applied for in writing.<br><br>

For purposes of computing the maximum residency status, the duration of official LOAs
is not counted as residency
    </p>
  </div>

  <div id="return-to-school">
    <h4 class="font-semibold text-2xl">Return to School</h4>
    <p>
      <br>Before the end of the leave, the student must apply for a re-admission to the school
through the Registrar’s Office. The student shall be classified as a Returnee upon their
return from an approved leave and shall enroll with this status.
</p>
  </div>

  <div id="awol-status">
    <h4 class="font-semibold text-2xl">AWOL Status</h4>
<p><br>Absence Without Official Leave (AWOL) is a status wherein a student:</p><br>
    <ul class="list-disc list-inside ml-5 space-y-1">
        <li>incurs absences more than the allowable limit; or</li>
        <li>takes an unofficial leave</li>
    </ul>
    <p><br>Any AWOL period is included in the reckoning of a student’s maximum residency.<br><br>
    A student on an AWOL status must apply for re-admission. However, the school reserves the right to refuse admission to the AWOL student.<br><br>
    When the student with AWOL status attempts to register for a subsequent term, he/she shall be required to seek the following endorsements and approval before being allowed to enroll:</p>
    <ol type="1" class="list-decimal list-inside space-y-2">
        <li>Endorsement from the Guidance Counselor/Student Affairs Officer</li>
        <li>Endorsement from the Program Head</li>
        <li>Approval from the Academic Head</li>
    </ol>
    <p><br>A student must follow the latest curriculum by the time they return to school.</p>
</div>

  <div id="cross-enrollment">
    <h4 class="font-semibold text-2xl">Cross Enrollment</h4>
    <p>
      <br>Cross Enrollment is defined as the enrollment of specific courses of a student in another
school other than their mother school as approved by both Registrars. The total load of the
cross enrollee in both schools should not exceed the maximum number of units required
by the curriculum.
    </p>
  </div>

    <div id="conditions-cross-enrollment">
    <h4 class="font-semibold text-2xl">Conditions for STI Students Cross Enrolling to Another School</h4>
     <p><br>Cross enrollment of STI students in another school (STI or other non-STI school) may be allowed on the following conditions:</p><br>
    <ol type="1" class="list-decimal list-inside space-y-2">
        <li>The student is in their terminal year;</li>
        <li>There is a conflict of schedule with other courses to be enrolled in;</li>
        <li>The course will not be offered within the student’s terminal year; and</li>
        <li>The course to be cross enrolled is not an On-the-Job Training/Practicum or Thesis course.</li>
    </ol>
    <p><br>In addition to the conditions above, cross enrollment is permitted in cases wherein:</p><br>
<ol class="list-[lower-alpha] list-inside ml-5 space-y-1">
    <li>An STI student requests to cross enroll to another STI school, the course to be cross enrolled can either be a minor or major course.</li>
    <li>An STI student requests to cross enroll in a non-STI school:
        <ul class="list-disc list-inside ml-5 space-y-1">
            <li>The course to be cross enrolled should only be a minor course;</li>
            <li>The host school has a comparable standard of quality education with STI;</li>
            <li>The course description and units of the course in the host school are similar to STI; and</li>
            <li>The minimum grade obtained by the STI student in the cross enrolled course should be 2.50 or above (or its equivalent) for it to be credited to STI.</li>
        </ul>
    </li>
</ol>
  </div>

  <div id="requirements-cross-enrollment">
    <h4 class="font-semibold text-2xl">Requirements for Students Cross Enrolling to Other School</h4>
 <ol type="1" class="list-decimal list-inside space-y-2"><br>
        <li>Permit to Cross Enroll from the Registrar of the mother school indicating the course, units, school year, and specific school to admit the student</li>
        <li>Certificate of Good Moral Character from the Guidance Counselor/Student Affairs Coordinator of the mother school</li>
    </ol><br>
    <p class="mt-2"><strong>Note:</strong> An STI student who will cross-enroll to another STI school or a non-STI school is required to enroll first in their mother school before a Permit to Cross Enroll is issued.</p>
</div>

    <div id="school-year">
    <h4 class="font-semibold text-2xl">School Year</h4>
    <p>
      <br>The school year for term-based programs is divided into two (2) terms of 18 weeks each,
inclusive of examination periods and class days lost due to natural or man-made
calamities. A midyear session of six (6) weeks follows the 2nd term.<br><br>

The school calendar shall serve as the guide for all academic and non-academic schedule
of activities to be observed unless otherwise changed by the school officials.
    </p>
  </div>

  <div id="student-classification">
    <h4 class="font-semibold text-2xl">Student Classification</h4>
 
  <p><b>1. According to Nationality</b>, the students may be classified as:</p>
  <p class="pl-6 mt-2">
    <b>1.1. Local Student</b> – Filipino student with previous high school or college studies taken either locally or abroad. <br><br>
    <b>1.2. Foreign Student</b> – Non-Filipino citizen with previous high school or college studies taken, whether locally or abroad.
  </p>

  <br>

  <p><b>2. According to Admission Status</b>, the students may be:</p>
  <p class="pl-6 mt-2">
    <b>2.1. New Students</b> – First-time enrollees in a particular STI school. <br><br>
    <span class="pl-6">
      <b>2.1.1. Incoming First Year Students</b> – First-time enrollees in a particular STI school with no tertiary education. <br><br>
      <b>2.1.2. External Transferees</b> – First-time enrollees in a particular STI school but with previous tertiary education in another CHED/TESDA-governed school.
    </span>
  </p>

  <p class="pl-6 mt-4">
    <b>2.2. Old Students</b> – Students with previous term enrollment at any STI school. <br><br>
    <span class="pl-6">
      <b>2.2.1. Returnees</b> – Students who return from an approved leave of absence. <br><br>
      <b>2.2.2. Readmitted Students</b> – Students with disciplinary case of Absence Without Leave (AWOL) but considered for readmission. <br><br>
      <b>2.2.3. Shiftees</b> – Students allowed to transfer from one academic program to another in the same STI school. <br><br>
      <b>2.2.4. Internal Transferees</b> – First-time enrollees in a particular STI school but with previous tertiary education in another STI school.
    </span>
  </p>

  <br>

  <p><b>3. According to Enrollment Status</b>, students may be classified as:</p>
  <p class="pl-6 mt-2">
    <b>3.1. Regular Students</b> – Students who carry the full-term load as prescribed in the curriculum. <br><br>
    <b>3.2. Irregular Students</b> – Students who do not carry the full-term load as prescribed in the curriculum due to either advanced or back courses. <br><br>
    <b>3.3. Cross Enrollees</b> – Students of another school, including other STI schools, who enroll at a particular STI school for credit in their mother school. <br><br>
    <b>3.4. Non-Credited Students</b> – Students permitted to attend classes without earning any credit.
  </p>

  <br>

  <p><b>4. According to Year Level</b>, students are classified according to the percentage of credited units successfully completed:</p>

  <div class="overflow-x-auto mt-4">
    <table class="min-w-full border border-gray-400 text-center">
      <thead>
        <tr>
          <th class="border border-gray-400 px-4 py-2">Year Level</th>
          <th class="border border-gray-400 px-4 py-2">4-Yr. Program</th>
          <th class="border border-gray-400 px-4 py-2">3-Yr. Program</th>
          <th class="border border-gray-400 px-4 py-2">2-Yr. Program</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-4 py-2">1<sup>st</sup></td>
          <td class="border border-gray-400 px-4 py-2">25% or less</td>
          <td class="border border-gray-400 px-4 py-2">33% or less</td>
          <td class="border border-gray-400 px-4 py-2">50% or less</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2">2<sup>nd</sup></td>
          <td class="border border-gray-400 px-4 py-2">26%–49%</td>
          <td class="border border-gray-400 px-4 py-2">34%–66%</td>
          <td class="border border-gray-400 px-4 py-2">More than 50%</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2">3<sup>rd</sup></td>
          <td class="border border-gray-400 px-4 py-2">50%–74%</td>
          <td class="border border-gray-400 px-4 py-2">More than 66%</td>
          <td class="border border-gray-400 px-4 py-2">—</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2">4<sup>th</sup></td>
          <td class="border border-gray-400 px-4 py-2">More than 74%</td>
          <td class="border border-gray-400 px-4 py-2">—</td>
          <td class="border border-gray-400 px-4 py-2">—</td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>

    <div id="study-load">
    <h4 class="font-semibold text-2xl">Study Load</h4>
    <p>
      <br>Study loads are prescribed for all students based on the approved curriculum of STI.
Students who carry the full-term load and follow the sequence of courses as prescribed in
the curriculum are classified as regular students, while irregular students do not carry the
full-term load or do not follow the sequence of courses.
</p>
  </div>

    <div id="term-load">
    <h4 class="font-semibold text-2xl">Term Load</h4>
    <p>
      <br>The standard regular term load is 24 units (credit or non-credit) or as prescribed
by the curriculum.
    </p>
  </div>

    <div id="midyear-load">
    <h4 class="font-semibold text-2xl">Midyear Load</h4>
    <p>
      <br>The midyear load should not exceed nine (9) units.
    </p>
  </div>

    <div id="underload">
    <h4 class="font-semibold text-2xl">Underload</h4><br>
    <p>
    Underload refers to a condition wherein a student takes a study load that is less than
    the prescribed number of units in their curriculum. A student may be allowed to have
    underload on any of the following conditions:
  </p>

  <ol class="list-decimal pl-8 mt-4 space-y-2">
    <li>
      Employment considerations either in or out of STI, with certification
      from the company.
    </li>
    <li>
      With warning or probationary status, with certification from
      the Guidance Counselor.
    </li>
    <li>
      Health reasons, certified by an attending physician.
    </li>
    <li>
      Unavailability of courses needed in the curriculum to complete the
      full load, certified by the Academic Head.
    </li>
  </ol>
  </div>

    <div id="overload">
    <h4 class="font-semibold text-2xl">Overload</h4>
    <p>
      <br>Overload refers to a condition wherein a student takes a study load that is more than the
prescribed number of units in their curriculum. A student may be allowed to have overload
provided they meet the following conditions:
    </p>
  </div>

    <div id="conditions-overload">
    <h4 class="font-semibold text-2xl">Conditions for Student Overload Units</h4>
    <div class="overflow-x-auto mt-4">
    <table class="min-w-full border border-gray-400 text-left text-base">
      <thead>
        <tr>
          <th class="border border-gray-400 px-4 py-2 font-semibold">Conditions</th>
          <th class="border border-gray-400 px-4 py-2 font-semibold">Allowable Overload Units</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-4 py-2 align-top">
            1<sup>st</sup><br>
            • A graduating student in their last two (2) regular terms of attendance; and<br>
            • With a GWA of at least 2.25 (82.50 – 85.49) in the previous term.
          </td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            Maximum of six (6) units per term.
          </td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2 align-top">
            2<sup>nd</sup><br>
            • A non-graduating student with a CGWA of at least 1.50 (92.00).
          </td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            Maximum of 30 units study load in the immediately succeeding regular term.
          </td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2 align-top">
            3<sup>rd</sup><br>
            • A non-graduating student who failed in one (1) course; and<br>
            • The course is a regular offering in the immediately succeeding term.
          </td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            Allow to enroll in the failed course. If the course to be enrolled is a prerequisite course,
            the student will be allowed to take it alongside the regular course subject to the following conditions:<br><br>
            a. If the student still fails the prerequisite course but passes the regular course, both courses are deemed failed.<br><br>
            b. However, if the student passes the prerequisite course but fails the regular course, they will have to re-enroll
            only the regular course in the succeeding term.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>

    <div id="standard-examinations">
    <h4 class="font-semibold text-2xl">Standard Periodical Examinations</h4>
  </div>

    <div id="periodical-examinations">
    <h4 class="font-semibold text-2xl">Periodical Examinations</h4>
    <p>
      <br>There are four (4) periodical examinations in a term: prelims, midterms,
pre-finals, and finals
    </p>
  </div>

  <div id="schedule">
    <h4 class="font-semibold text-2xl">Schedule</h4>
    <p><br>The schedule of examination is announced at least one (1) week before the first day
of the Periodical Examination.</p>
  </div>

  <div id="missed-examinations">
    <h4 class="font-semibold text-2xl">Missed Examinations</h4>
    <p>
      <br>A student who misses a periodical examination automatically obtains a raw score of
zero unless their failure to take the test is excused.

    </p>
  </div>

  <div id="special-examinations">
    <h4 class="font-semibold text-2xl">Special Examinations</h4>
     <p><br>
    A special examination may be given to a student who missed a periodical examination
    for any of the following reasons:
  </p>

  <ol class="list-decimal pl-8 mt-4 space-y-2">
    <li>
      Severe illness or accident, certified by an attending physician.
    </li>
    <li>
      Death of next of kin (grandparent, parent, brother or sister, spouse, child, or
      guardian), certified by a copy of the death certificate.
    </li>
  </ol>

  <p class="mt-4">
    The special examination must be taken not later than seven (7) calendar days after
    the approval of the application for special examination and before the start of
    the next periodical examination.
  </p>
  </div>

  <div id="grading-earned-credits">
    <h4 class="font-semibold text-2xl">Grading and Earned Credits</h4>
    <p>
      <br>Grades are determined by computing a student’s performance over the term for both
lecture and laboratory. A failing grade may be given to a student who does not meet the
attendance requirements.<br><br>

Credit is the number of units earned by a student for a course in which they have obtained
a passing grade.
    </p>
  </div>

  <div id="grading-system">
  <h4 class="font-semibold text-2xl mb-3">Grading System</h4>
  <p class="mb-4">
    The school adopts the following grading system with the corresponding equivalence:
  </p>

  <div class="overflow-x-auto mb-6">
    <table class="min-w-full border border-gray-400 text-sm">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2">Grade</th>
          <th class="border border-gray-400 px-3 py-2">Equivalence</th>
          <th class="border border-gray-400 px-3 py-2">Description</th>
        </tr>
      </thead>
      <tbody>
        <tr><td class="border px-3 py-2">1.00</td><td class="border px-3 py-2">97.50–100</td><td class="border px-3 py-2">Excellent</td></tr>
        <tr><td class="border px-3 py-2">1.25</td><td class="border px-3 py-2">94.50–97.49</td><td class="border px-3 py-2" rowspan="3">Very Good</td></tr>
        <tr><td class="border px-3 py-2">1.50</td><td class="border px-3 py-2">91.50–94.49</td></tr>
        <tr><td class="border px-3 py-2">1.75</td><td class="border px-3 py-2">88.50–91.49</td></tr>
        <tr><td class="border px-3 py-2">2.00</td><td class="border px-3 py-2">85.50–88.49</td><td class="border px-3 py-2" rowspan="2">Satisfactory</td></tr>
        <tr><td class="border px-3 py-2">2.25</td><td class="border px-3 py-2">81.50–85.49</td></tr>
        <tr><td class="border px-3 py-2">2.50</td><td class="border px-3 py-2">77.50–81.49</td><td class="border px-3 py-2">Satisfactory</td></tr>
        <tr><td class="border px-3 py-2">2.75</td><td class="border px-3 py-2">73.50–77.49</td><td class="border px-3 py-2">Fair</td></tr>
        <tr><td class="border px-3 py-2">3.00</td><td class="border px-3 py-2">69.50–73.49</td><td class="border px-3 py-2">Fair</td></tr>
        <tr><td class="border px-3 py-2">5.00</td><td class="border px-3 py-2">69.49 and below</td><td class="border px-3 py-2">Failed due to poor performance, absences, or withdrawal without notice</td></tr>
        <tr><td class="border px-3 py-2">DRP</td><td class="border px-3 py-2">Officially Dropped</td><td class="border px-3 py-2">Dropped with approved dropping slip</td></tr>
        <tr><td class="border px-3 py-2">INC</td><td class="border px-3 py-2">Incomplete</td><td class="border px-3 py-2">Incomplete requirements; applicable only to OJT/practicum courses</td></tr>
        <tr><td class="border px-3 py-2">P</td><td class="border px-3 py-2">Passed</td><td class="border px-3 py-2">To be used for courses specified as having non-numeric grades</td></tr>
        <tr><td class="border px-3 py-2">F</td><td class="border px-3 py-2">Failed</td><td class="border px-3 py-2">To be used for courses specified as having non-numeric grades</td></tr>
      </tbody>
    </table>
  </div>

  <p class="text-sm">
    A student who incurred INC for OJT/Practicum is given a maximum of one (1) year to complete all course requirements. Otherwise, the INC grade will be changed to 5.00.
  </p>
</div>

<div id="course-grade" class="mt-10">
  <h4 class="font-semibold text-2xl mb-3">Course Grade</h4>
  <p class="mb-4">
    The Course Grade is the measure of the student’s level of achievement in a course. It is given upon completion of all course requirements and is based on the weighted average of the periodical scores.
  </p>

  <div class="overflow-x-auto mb-4">
    <table class="min-w-full border border-gray-400 text-sm">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2">Period</th>
          <th class="border border-gray-400 px-3 py-2">Percentage</th>
        </tr>
      </thead>
      <tbody>
        <tr><td class="border px-3 py-2">Prelims</td><td class="border px-3 py-2">20%</td></tr>
        <tr><td class="border px-3 py-2">Midterms</td><td class="border px-3 py-2">20%</td></tr>
        <tr><td class="border px-3 py-2">Pre-Finals</td><td class="border px-3 py-2">20%</td></tr>
        <tr><td class="border px-3 py-2">Finals</td><td class="border px-3 py-2">40%</td></tr>
      </tbody>
    </table>
  </div>

  <p class="mb-4">
    To get the Course Grade, each periodical score is multiplied by its percentage weight. The total of these partial scores gives the Course Score, which is transmuted into the Course Grade using the Grading System Table.
  </p>

  <h5 class="font-semibold mb-2">Example:</h5>
  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-400 text-sm">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2">Period</th>
          <th class="border border-gray-400 px-3 py-2">Percentage</th>
          <th class="border border-gray-400 px-3 py-2">Periodical Score</th>
          <th class="border border-gray-400 px-3 py-2">Partial Score (Percentage × Periodical Score)</th>
        </tr>
      </thead>
      <tbody>
        <tr><td class="border px-3 py-2">Prelims</td><td class="border px-3 py-2">20%</td><td class="border px-3 py-2">80</td><td class="border px-3 py-2">16</td></tr>
        <tr><td class="border px-3 py-2">Midterms</td><td class="border px-3 py-2">20%</td><td class="border px-3 py-2">75</td><td class="border px-3 py-2">15</td></tr>
        <tr><td class="border px-3 py-2">Pre-Finals</td><td class="border px-3 py-2">20%</td><td class="border px-3 py-2">70</td><td class="border px-3 py-2">14</td></tr>
        <tr><td class="border px-3 py-2">Finals</td><td class="border px-3 py-2">40%</td><td class="border px-3 py-2">85</td><td class="border px-3 py-2">34</td></tr>
        <tr class="font-semibold">
          <td colspan="3" class="border px-3 py-2 text-right">Course Score</td>
          <td class="border px-3 py-2">79</td>
        </tr>
        <tr class="font-semibold">
          <td colspan="3" class="border px-3 py-2 text-right">Course Grade</td>
          <td class="border px-3 py-2">2.50</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div id="periodical-score" class="mt-10">
  <h4 class="font-semibold text-2xl mb-3">Periodical Score</h4>
  <p class="mb-4">
    Components of a periodical score are specified in the syllabus of each course.
    Below is a sample breakdown of periodical score components:
  </p>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-400 text-sm">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2">Component</th>
          <th class="border border-gray-400 px-3 py-2">Percentage (Other Programs)</th>
          <th class="border border-gray-400 px-3 py-2">BS in Accountancy</th>
        </tr>
      </thead>
      <tbody>
        <tr><td class="border px-3 py-2">Quizzes</td><td class="border px-3 py-2">20%</td><td class="border px-3 py-2">20%</td></tr>
        <tr><td class="border px-3 py-2">Performance Task</td><td class="border px-3 py-2">30%</td><td class="border px-3 py-2">—</td></tr>
        <tr><td class="border px-3 py-2">Major Examinations</td><td class="border px-3 py-2">50%</td><td class="border px-3 py-2">80%</td></tr>
        <tr class="font-semibold">
          <td class="border px-3 py-2 text-right">Total</td>
          <td class="border px-3 py-2">100%</td>
          <td class="border px-3 py-2">100%</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>


  <div id="release-of-grades">
    <h4 class="font-semibold text-2xl">Release of Grades</h4> <br>
     <ol class="list-decimal pl-6 space-y-2">
    <li>
      A faculty consultation period is scheduled prior to the release of the final grades.
      This is to provide an opportunity for the students and faculty members to validate
      the given grades before its submission to the Registrar’s Office.
    </li>
    <li>
      Grades of students with pending accountability will be withheld.
    </li>
    <li>
      The schedule of release of Course Grades is announced by the Registrar’s Office.
    </li>
  </ol>
  </div>

  <div id="gwa">
    <h4 class="font-semibold text-2xl">General Weighted Average</h4>
    <p class="mb-4">
    The General Weighted Average (GWA) is a measure of the overall scholastic achievement of a student.
    This is the weighted average grade that the student got from the curricular courses taken at STI, excluding:
  </p>

  <ul class="list-decimal list-inside mb-4">
    <li>Courses with non-numeric grades such as “P”, “F”, and “DRP”.</li>
    <li>Required non-credit courses identified in the program curriculum (such as National Service Training Program [NSTP]).</li>
  </ul>

  <p class="mb-4">Computation of the GWA is as follows:</p>

  <ol class="list-decimal list-inside mb-6 space-y-1">
    <li>Multiply the number of Units (Un) of each course prescribed in the curriculum by the Course Grade (CG) to get the Credit Points per Course (CPC).</li>
    <li>For a course retaken due to failure, every occurrence is considered individually.</li>
    <li>Add the credit points of all the courses to get the Total Credit Points (TCP).</li>
    <li>Divide the Total Credit Points (TCP) by the Total Number of Units (TUn) of all the courses, and round off to two (2) decimal places.</li>
  </ol>

  <div class="mb-6">
    <p class="italic text-center mb-2">Formulas:</p>
    <p class="text-center">CPC = CG × Un</p>
    <p class="text-center">TCP = CPC₁ + CPC₂ + CPC₃ + … + CPCₙ</p>
    <p class="text-center">GWA = TCP / TUn</p>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-400 text-sm">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2">Course</th>
          <th class="border border-gray-400 px-3 py-2">Course Grade (CG)</th>
          <th class="border border-gray-400 px-3 py-2">Unit (Un)</th>
          <th class="border border-gray-400 px-3 py-2">Credit Points per Course (CPC)</th>
        </tr>
      </thead>
      <tbody>
        <tr><td class="border px-3 py-2">Course 1</td><td class="border px-3 py-2 text-center">2.5</td><td class="border px-3 py-2 text-center">1</td><td class="border px-3 py-2 text-center">2.5</td></tr>
        <tr><td class="border px-3 py-2">Course 2</td><td class="border px-3 py-2 text-center">5</td><td class="border px-3 py-2 text-center">2</td><td class="border px-3 py-2 text-center">10</td></tr>
        <tr><td class="border px-3 py-2">Course 2 (retake)</td><td class="border px-3 py-2 text-center">2</td><td class="border px-3 py-2 text-center">2</td><td class="border px-3 py-2 text-center">4</td></tr>
        <tr><td class="border px-3 py-2">Course 3</td><td class="border px-3 py-2 text-center">3</td><td class="border px-3 py-2 text-center">3</td><td class="border px-3 py-2 text-center">9</td></tr>
        <tr><td class="border px-3 py-2">Course 4</td><td class="border px-3 py-2 text-center">2</td><td class="border px-3 py-2 text-center">4</td><td class="border px-3 py-2 text-center">8</td></tr>
        <tr><td class="border px-3 py-2">Course 5</td><td class="border px-3 py-2 text-center">1</td><td class="border px-3 py-2 text-center">5</td><td class="border px-3 py-2 text-center">5</td></tr>
        <tr class="font-semibold">
          <td class="border px-3 py-2 text-right">Totals</td>
          <td class="border px-3 py-2"></td>
          <td class="border px-3 py-2 text-center">TUn = 17</td>
          <td class="border px-3 py-2 text-center">TCP = 38.5</td>
        </tr>
        <tr class="font-semibold">
          <td class="border px-3 py-2 text-right">GWA</td>
          <td colspan="3" class="border px-3 py-2 text-center">2.26</td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>

  <div id="student-works">
    <h4 class="font-semibold text-2xl">Student Works</h4>
    <p>
      <br>In the case of student projects (documentation and software solutions) produced and
submitted as course requirements, these works are owned by the students. The school is
allowed free access to and use the student works to pursue or develop them for academic
purposes provided that there is no infringement of any intellectual property right.
    </p>
  </div>

  <div id="attendance">
    <h4 class="font-semibold text-2xl">Attendance</h4>
    <p>
      <br>All students are required to attend classes regularly and punctually. When a student is
tardy or absent, they are expected to assume full and independent responsibility for
the subject matter taught, discussed, assigned, etc. during their absence. The student
should likewise consult with the faculty member regarding their academic standing.<br><br>

A student who ceases to attend classes until the end of the term and/or exceeds the
maximum allowable absences will be given a grade of 5.00 with AWOL status.
    </p>
  </div>

  <div id="absences">
    <h4 class="font-semibold text-2xl">Absences</h4>
    <p>
      <br>
    A student who incurs absences of more than 20% (CHED MORPHE 2009 Art. 21 Sec. 101)
    of the class hours would automatically receive a failing grade for the course (unless an appeal
    is made and approved by the Academic Head).
  </p>

  <p class="mb-4">
    This maximum number of absences depends on the required class meetings per course that is equivalent to the following:
  </p>

  <div class="overflow-x-auto mb-6">
    <table class="min-w-full border border-gray-400 text-sm">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2" rowspan="2">Units</th>
          <th class="border border-gray-400 px-3 py-2" colspan="2">Lecture Hour/Term</th>
          <th class="border border-gray-400 px-3 py-2" colspan="2">Laboratory Hour/Term</th>
        </tr>
        <tr>
          <th class="border border-gray-400 px-3 py-2">Total</th>
          <th class="border border-gray-400 px-3 py-2">20%</th>
          <th class="border border-gray-400 px-3 py-2">Total</th>
          <th class="border border-gray-400 px-3 py-2">20%</th>
        </tr>
      </thead>
      <tbody>
        <tr><td class="border px-3 py-2 text-center">5</td><td class="border px-3 py-2 text-center">90</td><td class="border px-3 py-2 text-center">18</td><td class="border px-3 py-2 text-center">270</td><td class="border px-3 py-2 text-center">54</td></tr>
        <tr><td class="border px-3 py-2 text-center">4</td><td class="border px-3 py-2 text-center">72</td><td class="border px-3 py-2 text-center">14.4</td><td class="border px-3 py-2 text-center">216</td><td class="border px-3 py-2 text-center">43.2</td></tr>
        <tr><td class="border px-3 py-2 text-center">3</td><td class="border px-3 py-2 text-center">54</td><td class="border px-3 py-2 text-center">10.8</td><td class="border px-3 py-2 text-center">162</td><td class="border px-3 py-2 text-center">32.4</td></tr>
        <tr><td class="border px-3 py-2 text-center">2</td><td class="border px-3 py-2 text-center">36</td><td class="border px-3 py-2 text-center">7.2</td><td class="border px-3 py-2 text-center">108</td><td class="border px-3 py-2 text-center">21.6</td></tr>
        <tr><td class="border px-3 py-2 text-center">1</td><td class="border px-3 py-2 text-center">18</td><td class="border px-3 py-2 text-center">3.6</td><td class="border px-3 py-2 text-center">54</td><td class="border px-3 py-2 text-center">10.8</td></tr>
        <tr><td class="border px-3 py-2 text-center">TESDA</td><td class="border px-3 py-2 text-center">N</td><td class="border px-3 py-2 text-center">20% × N</td><td class="border px-3 py-2 text-center">N</td><td class="border px-3 py-2 text-center">20% × N</td></tr>
      </tbody>
    </table>
  </div>

  <p class="mb-4">
    Hence, using the table above, a student who is enrolled in a two-unit lecture course will not be allowed to exceed
    <strong>7.2 class hours of absences</strong>. Similarly, a student enrolled in a four-unit course with three (3) units
    of lecture and one (1) unit of laboratory must not have absences of more than <strong>10.8 class hours</strong> in either lecture or laboratory sessions.
  </p>

  <p class="mb-4">
    Three instances of tardiness are equivalent to one absence. Time lost due to late enrollment is considered time lost by absence.
  </p>

  <p>
    The student is expected to be responsible for keeping a record of their attendance in their enrolled courses.
    However, this may be verified with the concerned faculty member.
  </p>
    </p>
  </div>

  <div id="waiting-period">
    <h4 class="font-semibold text-2xl">Waiting Period</h4>
    <p>
     <br>Students are required to wait for the instructor to arrive within the first 25% of the class
duration. A student who leaves the classroom before the waiting period has elapsed will
be considered absent if the instructor arrives within the stipulated waiting period.<br><br>

A student who leaves the class after the waiting period has elapsed will not be
considered absent even if the instructor arrives beyond the waiting period and
conducts a class. <br><br>

Make-up classes shall be scheduled in case of a faculty member’s absences to complete
the required student contact hours for the course. In no case will the make-up class be
scheduled such that it conflicts with the ongoing classes of the students, nor shall
attendance be required. However, it is the responsibility of the student to catch up on the
lessons discussed during the said make-up class.
    </p>
  </div>

  <div id="suspension-of-classes">
    <h4 class="font-semibold text-2xl">Suspension of Classes</h4>
     <p class="mb-4">
    <br>Classes may be suspended due to any of the following conditions:
  </p>

  <ol class="list-decimal list-inside mb-4 space-y-1">
    <li>
      Announced by the appropriate government agency (inclement weather,
      transportation strike, etc.) and/or local government unit.
    </li>
    <li>
      Typhoon Signal Number 3 as declared by the Philippine Atmospheric, Geophysical,
      and Astronomical Services Administration (PAGASA).
    </li>
    <li>
      As determined by the school management.
    </li>
  </ol>

  <p class="mb-4">
    The written announcement of class suspension shall be posted at the school entrance,
    on the official STI social media platforms, and on STI eLMS throughout the day.
  </p>

  <p>
    Make-up classes for suspended classes may be scheduled, in which case schedules for
    make-up classes will be determined ahead of time and properly announced.
  </p>
  </div>

  <div id="course-sequence">
    <h4 class="font-semibold text-2xl">Course Sequence</h4>
    <p><br>All students must observe the course sequence prescribed by the curriculum.</p>
  </div>

  <div id="prerequisite">
    <h4 class="font-semibold text-2xl">Prerequisite</h4>
    <p>
      <br>Some courses may have prerequisites. A prerequisite is a required preliminary course
that must be passed before enrolling in the next level course. It is supposed to prepare
the students for a more advanced course.<br><br>

A student may only be allowed by the Registrar to enroll in the prerequisite and advanced
course simultaneously if they have attended the prerequisite course until the end of the
previous term but failed to pass it. The waiver of prerequisite, endorsed by the previous
teacher attesting to the attendance of the student during the whole term, shall be subject
to the approval of the Program Head. In the absence of the waiver due to the previous
teacher’s unavailability, the details of the attendance sheet from the teacher’s class record
may be used as a reference.<br><br>

A failure in the prerequisite course would automatically mean a failure in the advanced
course if taken simultaneously.<br><br>

Any violation of the course sequence due to factors other than the above would
invalidate the courses concerned.
    </p>
  </div>

  <div id="corequisite">
    <h4 class="font-semibold text-2xl">Corequisite</h4>
    <p>
      <br>Corequisite refers to a related course that must be taken at the same time as the related
course with which, it is identified as a corequisite. These two (2) courses are designed to
complement each other. Knowledge gained in the corequisite course is considered essential
to the success in the counterpart course.
    </p>
  </div>

  <div id="petitioned-classes">
    <h4 class="font-semibold text-2xl">Petitioned Classes</h4>
    <p>
      <br>Petitioned Class Is a student-initiated course offering that is not part of the courses
regularly offered in the curriculum and is conducted within an existing academic program.
    </p>
  </div>

  <div id="change-of-courses-schedules">
    <h4 class="font-semibold text-2xl">Change of Courses or Schedules</h4>
    <p>
      <br>Students are allowed to change courses or schedules within the two-week late
registration period after the class has started. Courses canceled during this period will
not appear in the student’s transcript.
    </p>
  </div>

  <div id="dropping-of-courses">
    <h4 class="font-semibold text-2xl">Dropping of Courses</h4>
    <p>
      <br>Dropping occurs after the official registration of the student. They are allowed to drop from
a course(s), without being given a failing grade, within seven (7) calendar days before the
midterm examination. The transcript will contain a grade of “DRP” for the course, earning
the student no credit(s).
    </p>
  </div>

  <div id="shifting-of-academic-program">
    <h4 class="font-semibold text-2xl">Shifting of Academic Program</h4>
    <p>
      <br>Students are allowed to shift their academic program as long as they satisfy the admission
requirements of the particular program.
    </p>
  </div>

  <div id="fees-payments">
    <h4 class="font-semibold text-2xl">Fees & Payments</h4>
   <p class="mb-4"> <br>
    All fees due during enrollment and within the term should be paid through the Cashier
    and/or any authorized payment partners. This includes, but is not limited to, the
    following charges:
  </p>

  <ul class="list-disc list-inside space-y-1">
    <li>Tuition and other school fees</li>
    <li>Miscellaneous fees</li>
    <li>Research/Thesis fees</li>
    <li>OJT Fees</li>
    <li>
      Curricular and non-curricular activities fees (educational tours, field trips,
      field study, and other related learning experiences)
    </li>
    <li>Graduation fees</li>
  </ul>
    </p>
  </div>

  <div id="payment-schemes">
    <h4 class="font-semibold text-2xl">Payment Schemes</h4>
    <p>
      <br>Payments may be made in cash or installments.
    </p>
  </div>

  <div id="installment">
    <h4 class="font-semibold text-2xl">Installment</h4>
      <p class="mb-4">
    <br>Installment payments are broken down as follows:
  </p>

  <div class="overflow-x-auto mb-6">
    <table class="min-w-full border border-gray-400 text-sm">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2 text-left">Installment</th>
          <th class="border border-gray-400 px-3 py-2 text-left">Payment Due</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border px-3 py-2">Down payment</td>
          <td class="border px-3 py-2">Upon enrollment</td>
        </tr>
        <tr>
          <td class="border px-3 py-2">1st installment</td>
          <td class="border px-3 py-2">Three (3) school days before the first day of Prelim exams</td>
        </tr>
        <tr>
          <td class="border px-3 py-2">2nd installment</td>
          <td class="border px-3 py-2">Three (3) school days before the first day of Midterm exams</td>
        </tr>
        <tr>
          <td class="border px-3 py-2">3rd installment</td>
          <td class="border px-3 py-2">Three (3) school days before the first day of Pre-final exams</td>
        </tr>
        <tr>
          <td class="border px-3 py-2">4th installment</td>
          <td class="border px-3 py-2">Three (3) school days before the first day of Final exams</td>
        </tr>
      </tbody>
    </table>
  </div>

  <p class="mb-4">
    The amount per installment is indicated on the issued Registration and Assessment Form (RAF).
  </p>

  <p>
    Students/Parents/Guardians must strictly follow the payment schedule to avoid further
    inconvenience and late payment charges.
  </p>
  </div>

  <div id="refund-of-payment">
    <h4 class="font-semibold text-2xl">Refund of Payment</h4>
    <p class="text-justify mb-4"><br>
    To be entitled to a refund, students should drop/change courses or withdraw and file in writing that is addressed to their respective 
    School Administrator/Deputy School Administrator, not later than the 14th calendar day from the start of classes.
  </p>

  <p class="text-justify mb-6">
    Charges shall be applied regardless of whether the students have actually attended classes or not.
  </p>

  <h5 class="font-semibold text-xl mb-3">The schedule is as follows:</h5>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-400 text-left text-lg">
      <thead >
        <tr>
          <th class="border border-gray-400 px-4 py-2 font-semibold">Date of filing for Dropping/Withdrawal</th>
          <th class="border border-gray-400 px-4 py-2 font-semibold">Penalty Charge</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-4 py-2">Before the start of classes</td>
          <td class="border border-gray-400 px-4 py-2">Registration fee for the term</td>
        </tr>
        <tr >
          <td class="border border-gray-400 px-4 py-2">Within seven (7) calendar days from the start of classes</td>
          <td class="border border-gray-400 px-4 py-2">10% of the total amount due for the term</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2">
            Beyond seven (7) calendar days but not after 14 calendar days from the start of classes
          </td>
          <td class="border border-gray-400 px-4 py-2">20% of the total amount due for the term</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2">Beyond 14 calendar days from the start of classes</td>
          <td class="border border-gray-400 px-4 py-2">100% of the total amount due for the term</td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>

  <div id="special-admission-foreign-students">
    <h4 class="font-semibold text-2xl">Special Admission Fee for Foreign Students</h4>
    <p>
      <br>Foreign students are required a special admission fee for additional processing
requirements from the Philippine government.
    </p>
  </div>

  <div id="financial-obligations">
    <h4 class="font-semibold text-2xl">Financial Obligations</h4>
    <p>
      <br>Students are reminded to settle all their financial obligations. Academic records/credentials
shall only be released once their obligation has been settled. Moreover, graduating
students will not be allowed to join the graduation rites until the dues are settled in full.

    </p>
  </div>

  <div id="honors">
    <h4 class="font-semibold text-2xl">Academic Honors</h4>
  </div>

  <div id="deans-presidents-honors">
    <h4 class="font-semibold text-2xl">Dean's and President's Honors List</h4>
    <p><br>
    STI recognizes the superior scholastic achievement of any student in a Baccalaureate or Pre-Baccalaureate program
    at the end of every regular term of each school year through the Dean’s and President’s Honors List. <br><br>
  </p>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-400 text-left text-lg mb-6">
      <thead>
        <tr>
          <th class="border border-gray-400 px-4 py-2 font-semibold w-1/3"></th>
          <th class="border border-gray-400 px-4 py-2 font-semibold text-center">Dean’s Honor List</th>
          <th class="border border-gray-400 px-4 py-2 font-semibold text-center">President’s Honors List</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-4 py-2 font-semibold align-top">Common conditions for inclusion in the list</td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            <ol class="list-decimal pl-6 space-y-2">
              <li>Not be found guilty of any minor or major offense throughout the student’s residency at STI.</li>
              <li>NSTP course grade is not included in the determination of the GWA but should not be less than 1.50.</li>
            </ol>
          </td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            <ol class="list-decimal pl-6 space-y-2" start="3">
              <li>
                Have a <span class="font-semibold">cumulative GWA of 1.50 or higher</span> based on all grades earned
                since the student’s initial enrollment up to the current term being evaluated.
              </li>
              <li>
                Note that cumulative GWA is a measure of the overall academic performance of a student weighted by all courses
                taken, multiplied by their credit hours.
              </li>
            </ol>
          </td>
        </tr>

        <tr >
          <td class="border border-gray-400 px-4 py-2 font-semibold align-top">Specific conditions for inclusion in the list</td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            <ol class="list-decimal pl-6 space-y-2" start="3">
              <li>Have a GWA of 1.50 or higher in the term being evaluated.</li>
              <li>Maintain a minimum course load of at least 80% of their regular load in the term being evaluated.</li>
              <li>Have no grades lower than 2.25 in all courses taken in the term being evaluated.</li>
            </ol>
          </td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            <ol class="list-decimal pl-6 space-y-2" start="5">
              <li>Be officially enrolled in all previous and current regular loads of academic units specified in the curriculum.</li>
              <li>Have no grades lower than 2.00 in all courses.</li>
              <li>Have no DRP in all previous and current study load as well as INC grade in OJT/Practicum courses.</li>
            </ol>
          </td>
        </tr>

        <tr>
          <td class="border border-gray-400 px-4 py-2 font-semibold align-top">Reward/s</td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            Certificate of Academic Recognition for the particular term
          </td>
          <td class="border border-gray-400 px-4 py-2 align-top">
            <ul class="list-disc pl-6 space-y-2">
              <li>Certificate of Academic Recognition for the particular term</li>
              <li>
                Privilege of unlimited absences in all courses for the succeeding regular term without receiving a failing mark,
                provided, however, that the student is not excused from keeping up with lessons, assignments, and examinations.
              </li>
              <li>
                Discount on tuition fee for the succeeding regular term depending on the cumulative GWA.
              </li>
            </ul>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <p class="text-justify">
    A student enrolled only in OJT/Practicum or Thesis course is <span class="font-semibold">NOT</span> eligible for both honors.
  </p>

  <p class="text-justify">
    In addition, the student eligible for the President’s Honors List (PHL) is qualified to apply for a discount on tuition fees for the succeeding regular term depending on the GWA of the term for which the honor was earned. The application for a scholarship must be made before the start of the regular term. The discount on tuition fees is only applicable to the school that awarded the PHL scholarship and cannot be used in another STI campus. <br><br>
  </p>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-400 text-left text-lg">
      <thead >
        <tr>
          <th class="border border-gray-400 px-4 py-2 font-semibold">GWA</th>
          <th class="border border-gray-400 px-4 py-2 font-semibold">% discount on Tuition Fee</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-4 py-2">1.00 to 1.10</td>
          <td class="border border-gray-400 px-4 py-2">100%</td>
        </tr>
        <tr >
          <td class="border border-gray-400 px-4 py-2">1.11 to 1.30</td>
          <td class="border border-gray-400 px-4 py-2">50%</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2">1.31 to 1.50</td>
          <td class="border border-gray-400 px-4 py-2">25%</td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>

  <div id="scholarships-financial-aid">
    <h4 class="font-semibold text-2xl">Scholarships and Financial Aid</h4>
     <p class="mb-4"><br>
    STI is at the forefront of promoting quality education that is equitably accessible to those
    with exemplary scholastic achievements and those academically deserving but financially
    challenged students. Thus, STI offers the following assistance subject to predetermined
    program guidelines:
  </p>

  <ol class="list-[lower-alpha] pl-6 space-y-4">
    <li>
      <span class="font-semibold">Academic Scholarships</span><br>
      Partial or full scholarship grant is given on a per term basis to those students who
      meet the prescribed academic requirements and maintain the required GWA.
    </li>
    <li>
      <span class="font-semibold">Financial Aid</span><br>
      Financial assistance in the form of scholarship grants, subject to the discretion
      of the school management, or remunerations for services rendered is available
      to students who require such assistance to meet their educational financial
      requirements at STI.
    </li>
  </ol>

  <p><br>
    <strong>Note:</strong> Scholarship grants awarded by STI must be applied by the student for renewal
    before the start of the succeeding regular term, subject to retention requirements as
    stated in the guidelines.
  </p>
  </div>

  <div id="student-assistantship-program">
    <h4 class="font-semibold text-2xl">Student Assistantship Program</h4>
    <p class="mb-4"><br>
    Qualified students who would like to study and work at STI at the same time may avail
    of the Student Assistantship Program.
  </p>

  <p class="mb-2 font-semibold">The following conditions must be satisfied by applicants for the Student Assistantship Program:</p>
  <ul class="list-disc pl-6 space-y-2 mb-6">
    <li>Must have completed SHS in STI, if not, must have completed at least two (2) terms of regular study in STI for tertiary level;</li>
    <li>Must have no present scholarships, assistance, or any similar program;</li>
    <li>Must have a general weighted average (GWA) of at least 2.25 or its equivalent in the preceding semester of application;</li>
    <li>Must have no failing grades in the entire stay at STI;</li>
    <li>Must have no previous record of any minor and major disciplinary offenses;</li>
    <li>Not cross-enrolled in any term; and</li>
    <li>No study overload in any term.</li>
  </ul>

  <p class="mb-2 font-semibold">All applicants must submit the following documents to the Registrar’s Office:</p>
  <ul class="list-disc pl-6 space-y-2 mb-6">
    <li>Duly accomplished Student Assistantship Application Form (SAAF);</li>
    <li>Income Tax Return (ITR) of parents or guardian;</li>
    <li>Letter of consent from parents or guardian; and</li>
    <li>Photocopy of grades earned in the previous semesters noted by the Registrar.</li>
  </ul>

  <p class="font-semibold mb-2">Note: In the absence of an ITR, any of the following may be submitted:</p>
  <ul class="list-disc pl-6 space-y-2 mb-6">
    <li>Payslip</li>
    <li>Original copy of the Certificate of Employment</li>
    <li>DTI/SEC registration (if self-employed)</li>
  </ul>

  <p class="mb-4">
    The Student Assistant may be assigned to answer inquiries regarding enrollment, do filing
    jobs, or assist in the laboratory. They shall not have access, however, to confidential
    records. Moreover, their scholarship will not involve an employer-employee relationship.
  </p>

  <p class="mb-4">
    In exchange for the number of hours (i.e. twenty hours of duty per week) rendered by the
    Student Assistant, they shall be paid the corresponding fees or the corresponding
    tuition fee discount.
  </p>

  <p>
    Inclusion in the program shall be revoked in case of excessive absences.
  </p>
  </div>

  <div id="ojt-practicum">
    <h4 class="font-semibold text-2xl">On-the-job Training (OJT)/Practicum</h4>
    <p>
      <br>The On-the-Job Training (OJT)/Practicum program provides work-based learning
experiences which serve as a venue for students to be exposed to career positions
relevant to their choice of academic degrees as well as open future career choices
towards gainful employment. The required number of OJT hours shall be prescribed by
the curriculum of the student’s program. The OJT Program shall be subject to the existing
policies and guidelines of the institution and the relevant government regulatory bodies.
</p>
  </div>

  <div id="academic-standing">
    <h4 class="font-semibold text-2xl">Academic Standing</h4>
    <p>
      <br>All students are expected to maintain good academic standing. This is achieved by
obtaining a passing rate in at least 75% of the total number of academic units officially
enrolled in the previous term.<br><br>
A student’s current academic standing is determined by their academic performance
in the previous term.
    </p>
  </div>

  <div id="academic-delinquency">
    <h4 class="font-semibold text-2xl">Academic Delinquency</h4>
    <p>
      <br>Students who fail to meet the minimum standards for good academic standing
are referred to as academically delinquent students. Depending on the academic
delinquency, these students shall be subject to a sanction (refer to the matrix of
academic delinquency).
    </p>
  </div>

  <div id="warning">
    <h4 class="font-semibold text-2xl">Warning</h4>
    <p>
      <br>A warning status shall be given to students in Good Standing who only passed 60% to
below 75% of the total number of units enrolled. A student’s warning status shall be
lifted by their passing at least 75% of the total number of academic units officially
enrolled in the succeeding term.
    </p>
  </div>

  <div id="academic-probation-retention">
    <h4 class="font-semibold text-2xl">Academic Probation and Retention</h4>
    <p class="mb-4"><br>
    Academic Probationary status shall be given to students under these conditions:
  </p>

  <ol class="list-decimal pl-6 space-y-4 mb-6">
    <li>
      <span class="font-semibold">First Academic Probationary Status</span>
      <ul class="list-disc pl-6 space-y-2 mt-2">
        <li>Students in Good Standing for only passing 45% to below 60% of the total number of units enrolled in;</li>
        <li>Students with Warning Status for only passing 60% to below 75% of the total number of units enrolled in.</li>
      </ul>
    </li>

    <li>
      <span class="font-semibold">Final Academic Probationary Status</span>
      <ul class="list-disc pl-6 space-y-2 mt-2">
        <li>Students in Good Standing for only passing below 45% of the total number of academic units enrolled in;</li>
        <li>Students with Warning Status for only passing 45% to below 60% of the total number of units enrolled in;</li>
        <li>Students with First Probationary Status for only passing 60% to below 75% of the total number of units enrolled in.</li>
      </ul>
    </li>
  </ol>

  <p>
    Students under academic probation shall be given a probationary load, less than the
    normal load, to be determined by the Academic Head.<br><br>

    The student’s academic probationary status shall be lifted by their passing at least 75% of
    the total courses enrolled for the succeeding term.
  </p>
  </div>

  <div id="dismissal">
    <h4 class="font-semibold text-2xl">Dismissal</h4>
     <p class="mb-4"><br>
    This shall be given to students under these conditions:
  </p>

  <ol class="list-decimal pl-6 space-y-2 mb-6">
    <li>Students with Warning Status for only passing below 45% of the total number of units enrolled in;</li>
    <li>Students with First Probationary Status for only passing below 60% of the total number of units enrolled in;</li>
    <li>Students with Final Probationary Status for only passing below 75% of the total number of units enrolled in.</li>
  </ol>

  <p>
    Generally, dismissed or disqualified students shall not be considered for re-admission
    by the Academic Head unless otherwise recommended by the Program Head for cases
    deemed acceptable.
  </p>
  </div>

  <div id="matrix-academic-delinquency">
    <h4 class="font-semibold text-2xl">Matrix of Academic Delinquency Status</h4>
     <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-400 text-left text-lg">
      <thead>
        <tr>
          <th class="border border-gray-400 px-4 py-2 font-semibold text-center align-middle" rowspan="2">Current Status</th>
          <th class="border border-gray-400 px-4 py-2 font-semibold text-center" colspan="4">% of Courses Passed</th>
        </tr>
        <tr>
          <th class="border border-gray-400 px-4 py-2 font-semibold text-center">Above 75%</th>
          <th class="border border-gray-400 px-4 py-2 font-semibold text-center">60% to 75%</th>
          <th class="border border-gray-400 px-4 py-2 font-semibold text-center">45% to 60%</th>
          <th class="border border-gray-400 px-4 py-2 font-semibold text-center">Below 45%</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-4 py-2 font-semibold text-center">Good Standing</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Good</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Warning</td>
          <td class="border border-gray-400 px-4 py-2 text-center">1st Probationary</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Final Probationary</td>
        </tr>
        <tr >
          <td class="border border-gray-400 px-4 py-2 font-semibold text-center">Warning</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Good</td>
          <td class="border border-gray-400 px-4 py-2 text-center">1st Probationary</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Final Probationary</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Dismissal</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2 font-semibold text-center">First Probationary</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Good</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Final Probationary</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Dismissal</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Dismissal</td>
        </tr>
        <tr >
          <td class="border border-gray-400 px-4 py-2 font-semibold text-center">Final Probationary</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Good</td>
          <td class="border border-gray-400 px-4 py-2 text-center">Dismissal</td>
          <td class="border border-gray-400 px-4 py-2 text-center"></td>
          <td class="border border-gray-400 px-4 py-2 text-center"></td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>

  <div id="remediation-programs">
    <h4 class="font-semibold text-2xl">Remediation Programs</h4>
    <p>
      <br>As part of a commitment to producing academically excellent students, STI encourages
all students to take advantage of offered programs designed to help overcome learning
difficulties. Such programs include:
    </p>
  </div>

  <div id="remedial-classes">
    <h4 class="font-semibold text-2xl">Remedial Classes</h4>
    <p>
      <br>Remedial classes are extra class meetings conducted to help students meet the minimum
learning requirements. Remedial classes may be arranged by the Faculty Member subject
to the approval of the Academic Head if more than 50% of the class failed to pass
the lessons as reflected in the students’ periodical examination.
    </p>
  </div>

  <div id="peer-tutoring">
    <h4 class="font-semibold text-2xl">Peer Tutoring</h4>
    <p>
      <br>Recognized student organizations are encouraged to set up peer tutoring services to
help students with learning difficulties in specific courses. The honor students may also
be tapped to provide tutoring services under faculty supervision. Arrangements shall
be facilitated by the Guidance Office or the Student Affairs Office and the
organization’s adviser.
    </p>
  </div>

  <div id="faculty-consultation">
    <h4 class="font-semibold text-2xl">Faculty Consultation</h4>
    <p>
      <br>The consultation period for each faculty member shall be specified at the beginning of the
term. Students, particularly those with learning difficulties, are encouraged to consult with
the Faculty Member concerned.
    </p>
  </div>

  <div id="graduation">
    <h4 class="font-semibold text-2xl">Graduation</h4>
  </div>
  
  <div id="requirements-graduation">
    <h4 class="font-semibold text-2xl">Requirements for Graduation</h4>
    <p><br>
    Only a bona fide STI student with the following qualifications may be allowed to apply for
    graduation from a CHED or TESDA program:
  </p>

  <ul class="list-decimal pl-8 space-y-2 text-lg">
    <li>
      <strong>Sufficient Residency</strong><br>
      To qualify for graduation from a particular STI school, the candidate must meet
      the minimum residency requirement. In addition, they must be officially
      enrolled during the last term prior to graduation at a particular STI school.
    </li>
    <li>Complete academic requirements</li>
    <li>No pending administrative case</li>
    <li>No pending obligations</li>
    <li>Complete admission requirements</li>
    <li>
      Official registrant of the STI Interactive Career Assistance and Recruitment System
      (<a href="http://www.i-cares.com">www.i-cares.com</a>)
    </li>
  </ul>
  </div>

  <div id="declaration-intent-graduate">
    <h4 class="font-semibold text-2xl">Declaration of Intent to Graduate</h4>
    <p>
      <br>Students who are in their last term are considered graduating students. A declaration
of their intent to graduate must be made during enrollment of the graduation term. The
deadline of submission is until the last day of the late enrollment period.<br><br>
The Registrar may call the attention of students with academic and/or financial deficiencies.
    </p>
  </div>

  <div id="list-candidates-graduation">
    <h4 class="font-semibold text-2xl">List of Candidates for Graduation</h4>
    <p>
      <br>Upon the release of the final grades, a list of candidates for graduation will be posted
by the Registrar’s Office. This list includes students who need to see the Registrar due to
insufficient graduation requirements.
    </p>
  </div>

  <div id="graduation-honors">
    <h4 class="font-semibold text-2xl">Graduation Honors</h4>
    <p>
      <br>STI recognizes students who have performed exceptionally not only in the aspect of
academics but also non-academics.
    </p>
  </div>

  <div id="classification-honors">
    <h4 class="font-semibold text-2xl">Classification of Honors</h4>
     <p class="mb-4 text-lg"><br>
    Awarded honors are based on the earned cumulative GWA of a student as follows:
  </p>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-400 text-lg">
      <thead>
        <tr>
          <th class="border border-gray-400 px-4 py-2 text-left">GWA</th>
          <th class="border border-gray-400 px-4 py-2 text-left">For Baccalaureate Programs<br><span class="font-normal">Latin Honors</span></th>
          <th class="border border-gray-400 px-4 py-2 text-left">Other Programs<br><span class="font-normal">English Honors</span></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-4 py-2">1.00–1.10</td>
          <td class="border border-gray-400 px-4 py-2">Summa Cum Laude</td>
          <td class="border border-gray-400 px-4 py-2">With Highest Honors</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2">1.11–1.30</td>
          <td class="border border-gray-400 px-4 py-2">Magna Cum Laude</td>
          <td class="border border-gray-400 px-4 py-2">With High Honors</td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-4 py-2">1.31–1.50</td>
          <td class="border border-gray-400 px-4 py-2">Cum Laude</td>
          <td class="border border-gray-400 px-4 py-2">With Honors</td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>

  <div id="eligibility-honors">
    <h4 class="font-semibold text-2xl">Eligibility for Honors</h4>
    <br>
      <ul class="list-decimal list-inside text-lg space-y-2">
    <li>Must have no grade lower than 2.25 in any course credited to the program they are graduating from.</li>
    <li>At least 75% of the total units earned towards the degree must have been taken in any STI school.</li>
    <li>Must have no record of a major offense throughout their stay in STI.</li>
  </ul>
  <p>
    <br><strong>Note:</strong> The grade requirement applies only to students whose <strong>Admit Year begins SY 2022
– 2023.</strong> Students who were admitted before the said school year shall be evaluated based
on the previous Graduation Honors criteria.
  </p>
  </div>

  <div id="graduation-dress-code">
    <h4 class="font-semibold text-2xl">Graduation Dress Code</h4>
    <p>
      <br>All graduating students shall wear the STI Graduation Stole over the prescribed attire:
    </p>
  </div>

  <div id="male-graduation-attire">
    <h4 class="font-semibold text-2xl">Male</h4>
    
     <br><ul class="list-disc list-inside text-lg space-y-2">
    <li>Well-pressed long sleeves / Barong Tagalog or Polo (Beige/Cream)</li>
    <li>Black slacks</li>
    <li>Black socks</li>
    <li>Black leather shoes</li>
  </ul>
  </div>

  <div id="female-graduation-attire">
    <h4 class="font-semibold text-2xl">Female</h4>
    <br>
    <ul class="list-disc list-inside text-lg space-y-2">
    <li>Formal top or dress with sleeves (Beige/Cream)</li>
    <li>If wearing a top, partner it with black slacks or a skirt.</li>
    <li>If wearing a dress or skirt, the length should be at least two (2) inches below the knee cap. Wearing a miniskirt is strictly prohibited.</li>
    <li>Formal shoes in a color that compliments the entire outfit. If shoes with heels are to be worn, they should not be more than 3 inches.</li>
    <li>Make-up, jewelry, and hairstyles must be kept at the minimum.</li>
  </ul>
  </div>

  <div id="special-graduation-awards">
    <h4 class="font-semibold text-2xl">Special Graduation Awards</h4>
  </div>

  <div id="sti-most-outstanding-thesis-award">
    <h4 class="font-semibold text-2xl">STI Most Outstanding Thesis (MOST) Award</h4>
    <p>
      <br>The STI Most Outstanding Thesis (MOST) Award is a program-based national-level
commendation given to a group of graduating students that exhibits the highest standard
of scholarly accomplishment. Nominations will come from among the outstanding thesis
projects in the entire STI network.
    </p>
  </div>

  <div id="graduation-credentials">
    <h4 class="font-semibold text-2xl">Graduation Credentials</h4>
     <p class="text-lg mb-4"><br>
    Upon clearance, the following graduation credentials will be issued to the graduates by request:
  </p>

  <ul class="list-decimal list-inside text-lg space-y-2 mb-4">
    <li>Official Transcript of Records</li>
    <li>Diploma/Certificate</li>
  </ul>

  <p class="text-lg">
    The actual issuance date of credentials may depend on the release of the Special Order for Graduation from the concerned government agencies.
  </p>
  </div>

  <div id="cpdt">
    <h4 class="font-semibold text-2xl">Centralized Printing of Diploma and Transcript of Records (CPDT)</h4>
    <p class="text-lg mb-4"><br>
    The printing of diplomas and transcripts of records (ToR) in STI is centralized to ensure the identity and authenticity of the STI graduates who can secure these documents. These documents shall only be issued from the STI Head Office.
  <br><br>

  The specific objectives of the CPDT are as follows:</p>

  <ul class="list-disc list-inside text-lg space-y-2 mb-4">
    <li>To ensure the integrity of the document;</li>
    <li>To provide an efficient process of diploma issuance;</li>
    <li>To standardize the format of the document;</li>
    <li>To prevent the proliferation of dubious documents;</li>
    <li>To facilitate the subsequent request of diploma and transcript of records.</li>
  </ul>

  <p class="text-lg">
    This applies to all STI schools and all academic programs offered.
  </p>
  </div>

</section>

<!-- ================= STUDENT SERVICES ================= -->
<section id="student-services" class="space-y-6 text-justify text-lg mt-16">
  <h3 class="text-3xl font-semibold mb-4">STUDENT SERVICES</h3>
            In its commitment to supporting and helping students reach their highest
potential, STI offers various programs and services, which include:<br>
  <div id="guidance">
    <h4 class="font-semibold text-2xl">Guidance & Counseling</h4>
    <p>
        <br>Guidance and Counseling Services assist the students in making the best out of their
college life. It is student-centered, preventive, and developmental. Through the Guidance
Office, individual and group counseling services are extended to assist the students in
dealing with various personal, educational, emotional, and career concerns.
    </p>
  </div>

  <div id="student-records">
    <h4 class="font-semibold text-2xl">Student Records</h4>
    <p>
        <br>Student Records Services, through the Registrar’s Office, make accurate academic and
financial records available to the students when needed. The Registrar’s Office maintains
all official student records, administration of registration activities, grade reports, and
graduation/diploma-related services. The confidentiality and integrity of these student
records are highly observed.
</p>
  </div>

  <div id="ict-services">
    <h4 class="font-semibold text-2xl">ICT Services</h4>
    <p>
       <br>ICT Services ensure the availability of ICT facilities, equipment, and other alternative
technology services. These services are also provided to support and enhance technical
development and promote academic enrichment. 
    </p>
  </div>

  <div id="library-services">
    <h4 class="font-semibold text-2xl">Library Services</h4>
    <p>
       <br>Library Services provide students with access to books, journals, and other informative
materials for academic advancement. The library serves as the resource center that
sources, screens, acquires, organizes, and circulates print and non-print reference materials
    </p>
  </div>

  <div id="sports">
    <h4 class="font-semibold text-2xl">Sports Development</h4>
    <p>
       <br> Sports Development Services are provided to foster the harmonious development of
the students’ mental, emotional, and physical well-being. Athletic environments, both
for indoor and outdoor activities, are made available to help establish interest and
develop their skills in physical activities, promote personal fitness, and instill the
value of teamwork.
    </p>
  </div>

  <div id="health-services">
    <h4 class="font-semibold text-2xl">Health Services</h4>
    <p>
        <br>Health Services are provided by the clinic to address medically-related concerns of students.
Students are assisted by offering first-aid treatment, free consultation, testing for prohibited
substances, and referrals to health specialists in nearby institutions in cases that require
further examination or treatment.
</p>
  </div>

  <div id="special-needs">
    <h4 class="font-semibold text-2xl">Special Needs & PWD</h4>
    <p>
        <br>STI accommodates students with special needs and/or persons with disabilities whether in
academic, vocational, or technical courses as stipulated in Republic Act 7277, also known
as the “Magna Carta for Persons with Disability” (as amended by RA 9442).
Students with Special Needs refers to a person who differs significantly from the
average student in mental characteristics, sensory abilities, neuromuscular or physical
characteristics, psychosocial characteristics, or has multiple handicaps or has a chronic
illness; or has a developmental lag to such an extent that he requires modified or
specialized instruction and services in order to develop to his maximum capability. <br><br>

STI designs programs and activities that are made available to persons with disabilities
and students with special needs. This shall be consulted with the students with disabilities
themselves, together with their teachers, parents and/or guardians, and other concerned
professionals whenever necessary.
    </p>
  </div>

  <div id="student-affairs">
    <h4 class="font-semibold text-2xl">Student Affairs</h4>
    <p>
       <br>Student Affairs Services, through the Student Affairs Office, ensure that all student
activities are aligned with STI’s commitment to helping students achieve their
highest potential. 
    </p>
  </div>

  <div id="offcampus-activities">
    <h4 class="font-semibold text-2xl">Off-Campus Activities</h4>
    <p>
        <br>The school provides the students with opportunities to learn and develop not just inside
the campus, but also outside. Thus, it administers off-campus activities that include both
curricular and non-curricular activities. The curricular activities include educational/field
trips, seminars, attendance to program-related events, field study/experiential learning/
related learning experiences, and the like as dictated in the curriculum/subject. On the
other hand, the non-curricular activities include mission-based, conventions, volunteer
work, advocacy projects, student organization-initiated sports, inter-school competitions,
culture and arts performances, and the like. The STI Administration believes that these
activities expose them to different scenarios, people, and advancement programs that will
help them become responsible and competent members of society. All STI Off-Campus
activities shall comply with the CHED Memorandum Order and TESDA Circular related to
the Policies and Guidelines on Local Off-Campus Activities.
    </p>
  </div>

  <div id="student-orgs">
    <h4 class="font-semibold text-2xl">Student Organizations</h4>
    <p>
        <br>The school provides students with opportunities to organize themselves and experience
relevant activities through student organizations. A student organization is a recognized
student body that aims to provide a fun environment conducive to student development
while abiding by the rules set forth by the STI Administration/Management. It shall adopt a
standard higher than what is expected with a vision towards excellence.
    </p>
  </div>

 <div id="student-publications">
    <h4 class="font-semibold text-2xl">Student Publications</h4>
    <p>
        <br>The school provides the students with opportunities to strengthen their ethical values,
practice critical, and creative thinking, and develop moral character and personal
discipline through student publications. A student publication is the issuance of any
printed and/or electronic materials that are independently published by, and which
meet the needs and interests of the studentry. School policies are set to guide these
student publications.
    </p>
  </div>

  <div id="placement-assistance">
    <h4 class="font-semibold text-2xl">Placement Assistance Services</h4>
    <p>
        <br>Placement Assistance Services, through the Alumni and Placement Office and in
coordination with the school’s academic personnel, conducts employment preparation
activities and presents employment opportunities for graduating students and alumni.
Services include but are not limited to the following:
    </p>

    <div id="eps" >
      <h4 class="font-semibold text-xl mt-4">Employment Preparation Seminars (EPS)</h4>
      <p>
        <br>Graduating students and alumni are provided with various seminars to prepare them for
the world of work. Seminars are primarily focused on but are not limited to resume writing,
handling of interviews, personality development, and current employment trends.
      </p>
    </div>

    <div id="mock-interview" >
      <h4 class="font-semibold text-xl mt-4">Mock Interview</h4>
      <p>
        <br>A mock interview simulates an actual job interview to enhance a graduate’s’ employability.
Students are exposed to real-life scenarios with a prospective employer, providing students
with insights on into their strengths and areas for improvement.
      </p>
    </div>

    <div id="video-resume" >
      <h4 class="font-semibold text-xl mt-4">Video Resume</h4>
      <p>
        <br>The school guides graduating students in creating a 3-minute video for employment.
The filmed presentation showcases a student’s academic qualifications and interests and
is often submitted in addition to a resume and cover letter.
      </p>
    </div>

    <div id="job-fairs" >
      <h4 class="font-semibold text-xl mt-4">Job Fairs / Virtual Recruitment</h4>
      <p>
        <br>The school provides graduating students and alumni with opportunities to apply, be
interviewed, and be hired by potential employers through Job Fairs. A Job Fair is a
recruitment activity wherein an STI school or a collaboration of STI schools invites legitimate
companies to gather in a specific location for recruitment purposes.
      </p>
    </div>

    <div id="virtual-career-fair" >
      <h4 class="font-semibold text-xl mt-4">Virtual Career Fair (VCF)</h4>
      <p>
        <br>A recruitment event that connects employers with students in a virtual space.
This includes a webinar hosted by the employer and is immediately followed by a
recruitment activity exclusively for STI graduating students.
      </p>
    </div>

    <div id="recruitment-day" >
      <h4 class="font-semibold text-xl mt-4">Recruitment Day</h4>
      <p>
        <br>The school provides graduating students and alumni with immediate and easy access
to employment opportunities through a recruitment service. A recruitment day enables
representatives from select partners to conduct recruitment activities in an identified
STI school after the job fair.
      </p>
    </div>

    <div id="icare-system" >
      <h4 class="font-semibold text-xl mt-4">I-CARE System</h4>
      <p>
        <br>The school provides employment assistance to graduating students and alumni through
an online job search system. The Interactive Career Assistance and Recruitment (I-CARE)
System (https://i-cares.sti.edu) enables graduating students and alumni to create,
store, and edit their resumes, browse employment opportunities posted by legitimate
companies, and apply to job openings online.
      </p>
    </div>
    </div>

  <div id="alumni-services">
    <h4 class="font-semibold text-2xl">Alumni Services</h4>
    <p>
        <br>Alumni Services, through the STI Alumni Association and its recognized alumni chapters,
maintains and enhances the school’s relationship with its graduates. The school’s alumni
chapter organizes programs such as annual homecoming, continuous learning seminars,
and sports activities.
    </p>
  </div>

  <div id="auxiliary-services">
    <h4 class="font-semibold text-3xl">Auxiliary Services</h4>
  </div>

  <div id="security-safety">
    <h4 class="font-semibold text-2xl">Security and Safety Services</h4>
    <p>
          <p>
  Security and Safety Services are provided by the school, your second home. The following measures are implemented to ensure a safe and sound learning environment for all students: <br><br>
</p>

<ol class="pl-6 space-y-3">
  <li>a.  Installation of CCTV cameras in the campus</li>
  <li>b.  Deployment of licensed and competent security personnel to do periodic rounds and random bag inspection and frisking</li>
  <li>c.  Safe, accessible (for persons with disabilities), and secure environment, buildings, and facilities that comply with government standards</li>
</ol>
    </p>
  </div>

  <div id="maintenance">
    <h4 class="font-semibold text-2xl">Maintenance</h4>
    <p>
        <br>Well-trained maintenance personnel are hired to ensure the cleanliness and orderliness
of the school facilities and its environs. Preventive measures are conducted regularly,
such as fumigation, pest control services, and the likes to ensure a healthy environment
and prevent communicable diseases.
    </p>
  </div>
</section>

<!-- ================= STUDENT BEHAVIOR & DISCIPLINE ================= -->
<section id="student-behavior" class="space-y-6 text-justify text-lg mt-16">
  <h3 class="text-3xl font-semibold mb-4">STUDENT BEHAVIOR & DISCIPLINE</h3>
            <br>As part of the STI community, you are expected to act with maturity,
integrity, and respect for people in authority, for your fellow students and
for the whole STI community. To ensure holistic development as an STI
student, you are expected to observe the following guidelines:

  <div id="student-appearance">
    <h4 class="font-semibold text-2xl">Student Appearance</h4>
    <p>
      <br>Each student shall adhere to the conventions of good grooming as a sign of respect to
oneself, others, and to STI as an academic institution.
    </p>
  </div>

  <div id="school-id">
    <h4 class="font-semibold text-2xl">School Identification Card</h4>
    <p>
<ol type="1" class="list-decimal list-inside space-y-2">
  <br><li>An official school identification (ID) card shall be issued to bona fide STI students.</li>
  <li>The ID (including the official strap) shall be part of the uniform and must be worn properly and visibly displayed at all times while inside the campus.</li>
  <li>The ID shall be free from any alteration or modification.</li>
  <li>The ID is non-transferable. It must not be tampered with or misused.</li>
  <li>The ID shall be required in all official transactions with the different offices of STI. It shall be surrendered upon permanently leaving the institution, e.g., end of the last term of stay at STI, transfer, or withdrawal.</li>
  <li>Students shall be required to surrender a damaged ID and apply for a replacement. An ID is considered damaged if the name and other pertinent details are unreadable or unrecognizable, or if the access feature is denied.</li>
  <li>Students who lost their ID shall be required to report the incident to the Registrar’s Office, submit an affidavit of loss, and apply for replacement.</li>
  <li>A temporary ID will be issued while the new ID is being processed.</li>
  <li>Students found guilty of giving false information regarding their ID shall be charged with a major offense.</li>
  <li>Only the STI official or endorsed school uniform is the acceptable attire for the ID picture taking of students.</li>
</ol>
</p>
  </div>

    <div id="school-id-replacement">
    <h4 class="font-semibold text-2xl">Procedure for ID Card Replacement</h4>
    <p>
<ol type="1" class="list-decimal list-inside space-y-2">
  <br><li>For lost IDs, secure a temporary gate pass from the school guard.</li>
  <li>Secure and fill out an Application for ID Replacement Form from the Registrar’s Office.</li>
  <li>Submit the accomplished form to the Registrar’s Office together with the notarized affidavit of loss or the damaged ID.</li>
  <li>Pay the corresponding replacement fee to the Cashier.</li>
  <li>Obtain your temporary ID by presenting the official receipt to the Registrar’s Office.</li>
</ol>
    </p>
  </div>

  <div id="student-uniform">
    <h4 class="font-semibold text-2xl">Student Uniform</h4>
    <p>
      <br>Certain programs, courses, or activities require a different set of uniforms. Only STI issued
or endorsed uniforms are allowed.<br><br>
For Physical Education (PE) classes, the prescribed shirt should be worn together with
jogging pants, rubber shoes, and sports socks.<br><br>
<strong>Note:</strong> The proper cut and size for uniforms should be observed. Skirt hemlines should not
be higher than three (3) inches from the knee and slits should not reach the upper thighs.
    </p>
  </div>

  <div id="wash-day">
    <h4 class="font-semibold text-2xl">Wash Day</h4>
    <p class="mb-4">
  <br>Wash days are specific days wherein students are allowed to wear STI proware shirts instead of the school uniform. Students are not permitted to wear clothes that will offend or scandalize the sensibilities of the academic community such as, but not limited to, the following:
</p>

<ul class="list-disc list-inside space-y-2">
  <li>Shorts, miniskirts, low riding pants, ripped jeans/pants with slips, rips, or holes higher than 7 inches above the knee</li>
  <li>Outfits or accessories with offensive images or language such as the promotion of drugs, tobacco, alcohol, glorification of death and mutilation, statements or implications of profanity, sexual or pornographic activity</li>
  <li>Blouses or dresses with plunging necklines, see-through, backless, strapless, body-hugging, or skin-tight outfits</li>
  <li>Midriffs, halter or crop tops, sando/tank tops or sleeveless, and tube-type shirts and blouses</li>
  <li>Skirt hemlines should not be higher than three (3) inches from the knee, and slits should not reach the upper thighs</li>
  <li>Use of wooden clogs, rubber or plastic slippers, and open-toe footwear</li>
</ul>

<p class="mt-4">
  Clothing must always be neat, clean, and worn as traditionally intended. Students may opt to wear uniforms or wash day clothes in accordance with their gender identity but must follow the set guidelines. They may consult their Discipline Officer or School Administrator for the process. Once confirmed, the student is expected to continue wearing their gender-affirming clothing throughout their stay with the institution.
</p>

  </div>

  <div id="grooming-haircut">
    <h4 class="font-semibold text-2xl">Grooming and Haircut</h4>
    <p><br>
      <ul class="list-disc list-inside space-y-2">
  <li>Hair must be kept neat, clean, and well-groomed.</li>
  <li>Colored hair is allowed.</li>
  <li>Makeup must be light and natural-looking.</li>
  <li>Wearing of items with offensive images or language, statements or implications of profanity, sexual or pornographic activity, or anything deemed by the school to be dangerous or a distraction to the learning environment is not acceptable.</li>
  <li>Sunglasses, bandannas, or caps are not to be worn indoors.</li>
  <li>Attire that may be used as a weapon should not be worn (e.g., steel-toed boots, chains, “dog collars,” or any items with spikes or studs).</li>
</ul>

<p class="mt-4">
  Specific programs, courses, or activities may require additional mandates for student appearance. For such cases, notices shall be provided by STI accordingly.
</p>

    </p>
  </div>

  <div id="student-decorum">
    <h4 class="font-semibold text-2xl">Student Decorum</h4>
    <p class="mb-4"><br>
  STI is not only concerned with the academic development of its students, but also with their character formation. Every STI student is expected to be refined in thoughts, words, and actions. An STI student should:
</p>

<ol type="a" class="list-[lower-alpha] list-inside space-y-3">
  <li>
    Uphold the academic integrity of the school, endeavor to achieve academic excellence, and abide by the rules and regulations governing academic responsibilities and moral integrity. Thus, in submitting any academic work, students are expected to properly cite references, direct quotes, and other sources including, but not limited to, data obtained from tables, illustrations, figures, pictures, images, and videos, following the prescribed format of the discipline (i.e., American Psychological Association, Modern Language Association). This also applies to:
    <ul class="list-disc list-inside mt-2 space-y-1">
      <li>Previous works submitted to other courses that are results of collaborative or group effort</li>
      <li>Computer codes written to accomplish a task or any activities required in any programming courses</li>
    </ul>
  </li>

  <li>
    Observe the usual norms of courtesy and etiquette in all areas of interpersonal relationships. Any act to the contrary, including unfavorable or offensive remarks about other persons regardless of their sex, creed, race, status, or political affiliation, may be deemed prejudicial to the enrollment or alumni status of the students concerned.
  </li>
  <li>
    Strive for student development by joining and actively participating in various activities sponsored by the school and recognized student organizations. It is strictly prohibited to form or be a member of any organization, fraternity, or sorority that is known to advocate, tolerate, or engage in violence or immoral behavior.
  </li>
  <li>
    Strictly observe classroom, laboratory, library, and other school office procedures.
  </li>
  <li>
    Refrain from exhibiting boisterous conduct, such as loitering, whistling, loud talking, or any other action that may distract others from their studies.
  </li>
  <li>
    Strive to develop healthy interactions with other students. However, acts or gestures which tend to offend other members of the community, including public displays of physical intimacy of the opposite or same sex, are not tolerated.
  </li>
  <li>
    Inform their parent or guardian of the following:
    <ul class="list-disc list-inside mt-2 space-y-1">
      <li>Rules and regulations expressed in this handbook</li>
      <li>Their academic standing and the possible consequences of excessive absences, dropping, failures, or gross misconduct</li>
    </ul>
  </li>
</ol>

  </div>

  <div id="anti-bullying">
    <h4 class="font-semibold text-2xl">Anti-Bullying & Anti-Cyberbullying Law Policy</h4>
<p>
  <br>STI is committed to providing a healthy learning environment where students support
and respect each other. Thus, within the school, it is made clear that bullying will not
be tolerated. “Bullying shall refer to any severe or repeated use by one (1) or more
students of a written, verbal, or electronic expression, or a physical act or gesture, or
any combination thereof, directed at another student that has the effect of actually
causing or placing the latter in reasonable fear of physical or emotional harm or
damage to their property; creating a hostile environment at school for the other
students, infringing on the rights of the other student at school; or materially and
substantially disrupting the education process or the orderly operation of a school.”
(Republic Act No. 10627, “Anti-Bullying Act of 2013”).<br><br>

Due to the advancement in technology and social media, emphasis is given on the
prevention of bullying in its electronic expression: Cyberbullying. Cyberbullying shall
refer to acts of cruelty committed using the internet or any form of electronic media or
technology that has the effect of stripping one’s dignity or causing reasonable fear of
physical or emotional harm.<br><br>

Strategies and mechanisms against bullying and cyberbullying (e.g., conducting antibullying/cyberbullying orientations to students and personnel, academic and discipline
policies, guidance and counseling, information dissemination through student-teacherparent leaflets, etc.) are meant to increase awareness and address the unacceptable
nature of bullying in and around the school.<br><br>

Bullying and cyberbullying behavior are confronted clearly and pursued beyond the
mere application of sanctions. Students who persist in bullying/cyberbullying, despite
counseling and support, are given sanctions based on this handbook. Sanctions
imposed will take into account the severity of the bullying/cyberbullying incident.
</p>
  </div>

  <div id="anti-hazing">
    <h4 class="font-semibold text-2xl">Anti-Hazing Law Policy</h4>
    <p>
      <br>STI is committed to ensuring a peaceful environment where camaraderie among
students is fostered through various interest groups or clubs inside the campus. Every
student organization is prohibited from using any form of violence, force, threat, or
intimidation as a prerequisite for admission. Any STI student who is found to have
committed or has conspired to commit the aforementioned shall be subject to Republic
Act No. 11053, otherwise known “Anti-Hazing Act of 2018,” and appropriate disciplinary
action provided in this handbook.
    </p>
  </div>

  <div id="anti-harassment">
    <h4 class="font-semibold text-2xl">Anti-Sexual Harassment Policy</h4>
    <p>
      <br>STI is committed to creating and maintaining an environment where all members of the STI
community are free to study without fear of harassment of a sexual nature. STI adheres
to Republic Act 7877, otherwise known as the “Anti-Sexual Harassment Act of 1995,”
which considers all forms of sexual harassment in the employment, education, or training
unlawful and contrary to the dignity of every individual, as well as the latter’s guarantee to
respect of human rights. Given the seriousness of this matter, STI promulgates appropriate
rules and regulations defining the offense of sexual harassment and outlining the
procedure in the investigation and imposition of administrative sanctions in such cases.
    </p>
  </div>

  <div id="gender-development">
    <h4 class="font-semibold text-2xl">Gender & Development Policy</h4>
    <p>
      <br>STI recognizes gender sensitivity as it pertains to one’s effort to show how gender shapes
the role of women and men in society, including their role in the development and how
their relationship affects each other. In support of the CHED Memorandum Order 01
series of 2015 entitled “Establishing the Policies and Guidelines on Gender and
Development in the Commission on Higher Education and Higher Education Institutes
(HEIs),” STI promotes gender awareness by appointing Gender and Development focal
persons in each school to pursue and implement programs, projects, and activities that will
contribute to the achievement of women’s empowerment and gender equality. It shall
also adopt gender mainstreaming in the academe as one of the strategies in educating
and informing various sectors of society on the need to recognize and respect the rights
of women and men
</p>
  </div>

  <div id="prohibited-items">
    <h4 class="font-semibold text-2xl">Smoking, Vaping, Prohibited Drugs, Paraphernalia
or Illegal Substances, and Dangerous Weapons</h4>
  <br>STI is committed to maintaining and sustaining a safe, healthy, and conducive learning environment for its students that should be entirely free from smoking, prohibited drugs, paraphernalia, and illegal substances, as well as deadly weapons or dangerous materials or instruments. <br><br>

  To ensure that this is achieved, the following measures shall be observed:
</p>

<ol type="1" class="list-decimal list-inside space-y-2">
  <li>Conduct student orientation, counseling, and mentoring to students on the negative effects of cigarette smoking/vaping, prohibited drugs, carrying deadly or dangerous weapons or materials/instruments.</li>
  <li>Engage students in meaningful programs and activities that promote their welfare and development.</li>
  <li>Conduct random drug tests for students every term in accordance with the provisions of Republic Act 9165, otherwise known as the “Comprehensive Dangerous Drugs Act of 2002.”</li>
  <li>Conduct bag inspection of those coming in and out of the school premises.</li>
  <li>Ban smoking, sale, or distribution of e-cigarette or tobacco products in compliance with the provisions of Republic Act 9211, otherwise known as the “Tobacco Regulation Act of 2003,” and Executive Order No. 26, Series of 2017, “Providing for the Establishment of Smoke-Free Environment in Public and Enclosed Places.”</li>
</ol>
  </div>

   <div id="random-drug-testing">
    <h4 class="font-semibold text-2xl">Random Drug Testing</h4>
    <p>

    <br>With its commitment to provide optimum value to its stakeholders and to ensure that the
students are free from the use of dangerous drugs, STI complies with the provisions in
Republic Act No. 9165, otherwise known as the “Comprehensive Dangerous Drugs Act of
2002” and its Implementing Rules and Regulations, the Dangerous Drugs Board Regulation
No. 6, series of 2003, as amended by Dangerous Drugs Board Regulation No. 3, series of
2009, and CHED Memorandum Order no. 18 series of 2018 Implementing Guidelines for
the Conduct of Drug Testing in all Higher Education Institutions (HEI’s). This aims to:<br><br>

<ul class="list-disc list-inside space-y-2">
<li>Deter students from using prohibited drugs and other illegal substances;</li>
<li>Determine the occurrence of drug users among the students; and</li>
<li>Facilitate the treatment and rehabilitation of confirmed drug users or dependents.</li>
</ul><br>

All students enrolled are subject to random drug testing without their necessary
concurrence and knowledge. The results of the tests are kept confidential and for the
evaluation of the school only. <br><br>

Students who are found to be positive for drug use after the confirmatory analysis will
be informed of their test results with utmost secrecy and confidentiality. The parents/
guardians of the “confirmed positive” students will be informed and required to attend
a scheduled case conference. No “confirmed positive” student shall be grounded for
expulsion or given any disciplinary action and should not be reflected in any and all
academic records but they are required to undergo an intervention program under the
supervision of a Department of Health (DOH)-accredited facility or physician, private
practitioners, or social worker, in consultation with parents/guardians.<br><br>  

However, a student who has undergone an intervention program but was found to be
“confirmed positive” for the second time shall be sanctioned with either non-readmission or
expulsion in accordance with the STI Drug Testing Policy.
    </p>
  </div>  

   <div id="electronic-gadget-rule">
    <h4 class="font-semibold text-2xl">Electronic Gadget Rule</h4>
    <p>
      <br>Students should strive to keep their classrooms clean, pleasant, and conducive to learning.
Chairs and tables must be aligned at all times. Lights, electric fans, and air conditioners
should be turned off whenever the students leave the room or if not in use. <br><br>

Students are also encouraged to keep the school building, study areas, and areas within the
school property clean.<br><br>

In any incident of destruction, damaging, tampering, or losing of school property, the school
reserves the right to charge to the concerned student/s the cost of damage, including labor
or repair.</p>
  </div>

   <div id="social-media-policy">
    <h4 class="font-semibold text-2xl">Social Media Policy</h4>
    <p><br>STI is dedicated to nurturing an environment of mutual respect wherein members of its community are engaged in positive and responsible online behavior. Students and other members of the STI community are expected to be cautious when engaging in any action on social media that may impact the privacy, dignity, or rights of the school, groups, or individuals, including themselves. This shall be accomplished by:</p>
<br>
<ol type= "1" class="list-decimal list-inside space-y-2 ml-6">
    <li>Reflecting on the potential impact of the content to be shared or posted to themselves or to others<br></li>
    <li>Maintaining appropriate boundaries when interacting with school personnel on social media<br></li>
    <li>Adhering to intellectual property rights<br></li>
    <li>Ensuring that when representing the school, posted content is aligned with the school’s values and policies<br></li>
  </ol><br>

<p>STI has the right to request the removal of any content that may risk the reputation of the school or a member of its community from a social media account.</p>

  </div>
  
   <div id="data-privacy-policy">
    <h4 class="font-semibold text-2xl">Data Privacy Policy</h4>
    <p><br>
    In accordance with the Data Privacy Act of 2012 (RA 10173), STI is committed to ensuring the confidentiality and security of information provided to the schools.<br>
    General provisions on how the institutions use, store, and retain collected information can be accessed via
    <a href="https://www.sti.edu/dataprivacy.asp">https://www.sti.edu/dataprivacy.asp</a>.
    To help keep confidential details secure, students and other members of the STI community should observe the following:<br><br>
  </p>
  <ol type="1" class="list-decimal list-inside space-y-2 ml-6">
    <li>Refrain from sharing sensitive or confidential information<br></li>
    <li>Review privacy settings in social media and other platforms regularly<br></li>
    <li>Ensure that all devices are locked if not in use<br></li>
    <li>Check the security of the platform before opening them<br></li>
    <li>Avoid logging to personal accounts on free or public Wi-Fi<br></li>
  </ol>
  </div>
  
   <div id="student-discipline">
    <h4 class="font-semibold text-2xl">Student Discipline</h4>
  </div>

    <div id="discipline-committee">
    <h4 class="font-semibold text-2xl">Discipline Committee</h4>
<p><br>
    The Discipline Committee has jurisdiction over all cases involving student discipline and the imposition of sanctions.<br>
    The committee’s tasks revolve around investigating cases involving student discipline, where recommendations have to be given at the end of the investigation.<br>
    It shall be composed of the following:<br><br>
  </p>

  <ol type="1" class="list-decimal list-inside space-y-2 ml-6">
    <li>Academic Head as Ex Officio Chairman. If unavailable, the Academic Head shall assign the Program Head to the role.<br></li>
    <li>Two (2) Faculty Representatives to be selected by the Academic Head<br></li>
    <li>Staff Representative to be appointed by the School Administrator/Deputy School Administrator<br></li>
    <li>A Representative from the Commission on Higher Education (CHED) or Technical Education and Skills Development Authority (TESDA), if available<br></li>
  </ol>

  <p><br>
    If any of the above is a respondent or involved in the dispute, another official representative shall be designated.
  </p>
  </div>
  
    <div id="initial-settlement">
    <h4 class="font-semibold text-2xl">Initial Settlement</h4>
    <p>
      <br>The Academic Head, Program Head/s, and the Discipline Officer shall have joint and
equal authority or control over all student disputes requiring mediation. However, the
Discipline Committee shall be convened to hear complaints or disputes or both that involve
the imposition of disciplinary measures.
    </p>
  </div>
  
    <div id="disciplinary-sanctions">
    <h4 class="font-semibold text-2xl">Implementation of Disciplinary Sanctions</h4>
    <p>
      <br>To help ensure an atmosphere conducive to learning, a special mechanism shall be
established to administer appropriate and reasonable sanctions to erring members of
the school community subject to the requirements of due process, as well as to resolve
disputes among and between them.
    </p>
  </div>
  
    <div id="complaints">
    <h4 class="font-semibold text-2xl">Student Complaints</h4>
    <p>
      <br>Student complaints may be filed in writing with the Discipline Committee headed by the
Academic Head. When applicable, an amicable settlement between the Complainant
and the Respondent may be initially pursued.<br><br>

The Discipline Committee (or the Academic Head) may, on its own initiative, take notice
of any breach of discipline or rule involving students even without a complaint.
</p>
  </div>
  
    <div id="disciplinary-cases-procedure">
    <h4 class="font-semibold text-2xl">Procedure for Disciplinary Cases</h4>
    <p><br>
    Due process is observed for cases that need to be investigated and may result in possible dismissal.<br><br>
  </p>

  <ol type="1" class="list-decimal list-inside space-y-2 ml-6">
    <li>The Complainant shall submit a written complaint to the Discipline Officer. If there is no assigned Discipline Officer, it should be submitted to the Academic Head, the Ex Officio Chairman of the Discipline Committee. When applicable, an amicable settlement between the Complainant and the Respondent may be initially pursued.<br></li>

    <li>The Discipline Officer or Academic Head shall set a meeting with the Complainant for consultation and discussion of their rights and possible consequences of pursuing the complaint.<br></li>

    <li>If the Complainant decides to pursue the case, the written complaint shall be forwarded to the members of the Discipline Committee for a resolution not later than 30 working days after its receipt of the complaint.<br></li>

    <li>The Respondent shall be notified in writing of the complaint filed against them which shall contain the nature and cause of the accusation against them. The notification shall, in all cases, direct the respondent to answer the accusation within three (3) working days from receipt. Failure to do so within the prescribed period shall not delay the proceedings.<br></li>

    <li>For student respondents who are minors, the parents or guardian shall likewise be notified in writing of the cause and accusation leveled against the Respondent.<br></li>

    <li>The Respondent shall be advised by the Head of the Discipline Committee of their rights and of the procedure to be followed in the proceedings resolving their case.<br></li>

    <li>Prior to the hearing, the Discipline Committee must refer the Complainant and the Respondent to the Guidance Counselor and schedule a separate one-on-one session for behavioral, emotional, and welfare purposes.<br></li>

    <li>The Discipline Committee may schedule hearings for the reception of evidence to enable it to arrive at a proper resolution of the complaint. In the case of a hearing wherein the Respondent is summoned by the Committee, the notice of hearing shall be given to the Respondent at least five (5) working days before the scheduled hearing. The Respondent shall be allowed to present evidence on their behalf.<br></li>

    <li>Taking into consideration all the evidence gathered during the proceedings, the Discipline Committee shall draft a resolution with a finding as to the liability of the Respondent. The resolution shall also contain a recommendation to the President/School Administrator/Deputy School Administrator as to the imposition of any penalty whenever applicable. The resolution shall be submitted to the President/School Administrator not later than 30 working days from the close of reception of evidence before the Committee.<br></li>

    <li>Upon receipt of the resolution of the Discipline Committee, the President/School Administrator/Deputy School Administrator shall make a decision as to whether or not to impose sanctions upon the Respondent. The decision shall be in writing and the same shall be served upon the Respondent. In the case of a Respondent being a minor, the parents or guardian of said Respondent shall also be served with the same. A copy of the resolution addressed to the Complainant should also be provided.<br></li>

    <li>An appeal to the President/School Administrator/Deputy School Administrator’s decision may be made within 10 working days from receipt of the said decision by an appealing party. The appeal may come from either the Respondent or the Complainant and shall be addressed to the Office of the President/School Administrator.<br></li>

    <li>The School Administration reserves the right to place a Respondent under suspension pending appeal.<br></li>

    <li>The decision of the Discipline Committee shall be final and executory if not appealed within the given period.<br></li>

    <li>The Discipline Committee shall give a copy of the final resolution along with a Referral Form to the Guidance Office prior to the imposition of any disciplinary action or sanction.<br></li>

    <li>The Discipline Committee shall call for a conference with the Respondent and their parents or guardian and a separate conference with the Complainant and their parents or guardian to discuss the final resolution. Both parties shall be provided with their own copy of the final resolution.<br></li>
  </ol>
  </div>
    
    <div id="guidance-discipline-procedure">
    <h4 class="font-semibold text-2xl">The Procedure of the Guidance and Counseling Office in Handling Discipline Cases Referred by the Discipline Committee</h4>
     <br><ol type="1" class="list-decimal list-inside space-y-2 ml-6">
    <li>The Guidance Counselor shall receive a Referral Form from the Discipline Committee with a copy of the Incident Report.<br></li>
    <li>The Guidance Counselor will conduct a conference with the student/s concerned.<br></li>
    <li>The Guidance Counselor will provide the Discipline Committee with initial feedback.<br></li>
    <li>The Guidance Counselor will conduct a follow-up conference with the student/s after the decision/resolution of the Discipline Committee has been made.<br></li>
    <li>The Guidance Counselor will provide the Discipline Committee with feedback and a follow-up plan of action for the student/s.<br></li>
  </ol>
  </div>

    <div id="disciplinary-measures">
    <h4 class="font-semibold text-2xl">Disciplinary Measures</h4>
    <p>
      <br>The approach of the school to discipline has always been preventive and formative. It is
not punitive but rather educative.<br><br>

A comprehensive and intense information campaign is initiated during the first few days
of classes to ensure that all school rules and policies are communicated and understood
by all concerned.<br><br>

A detailed warning system is in place for minor offenses. However, should all preventive
measures and mechanisms fail, the school, through proper authorities, applies
disciplinary measures or actions.<br><br>

Disciplinary measures or actions are meant to teach students the principles and ideals
of justice to help them achieve self-discipline, as well as to enjoin them in developing
and sustaining an atmosphere conducive to learning.
    </p>
  </div>
     
    <div id="corrective-actions">
    <h4 class="font-semibold text-2xl">Corrective Actions to Minor and Major Offenses</h4>
    <p>
      <br>Corrective Actions are disciplinary measures that are imposed corresponding to the
severity of the offense/s done by an erring student.
    </p>
  </div>
       
    <div id="verbal-oral-warning">
    <h4 class="font-semibold text-2xl">Verbal/Oral Warning</h4>
    <p>
      <br>A Verbal/Oral warning is a disciplinary measure given to a student who has committed
minor violations. This is to call the attention of the student that they have not observed
the appropriate behavior expected of them. It is a reminder or reprimand to a student
who committed a minor offense for the first time. This shall be included on the student’s
record since this shall be considered an initial warning.
    </p>
  </div>
      
    <div id="written-apology">
    <h4 class="font-semibold text-2xl">Written Apology</h4>
    <p>
      <br>A Written Apology is a corrective action in which a student is required to write a letter of
apology. This is imposed on a case-to-case basis and shall be included on the
student’s record.
  </p>
  </div>

    <div id="written-reprimand">
    <h4 class="font-semibold text-2xl">Written Reprimand</h4>
    <p>
      <br>A Written Reprimand is a corrective action that is issued by the Discipline Committee.
The student is given a formal letter or notice of any violation of the school rules and
regulations. The student-specific misbehavior, together with the original copy of the
written reprimand form, is put on the student’s record.
    </p>
  </div>
      
    <div id="corrective-reinforcement">
    <h4 class="font-semibold text-2xl">Corrective Reinforcement</h4>
    <p>
      <br>During the period of corrective reinforcement, the student is still allowed to attend their
classes. However, they have to be scheduled for one-on-one session after their
last class period and to accomplish tasks as determined and given by the assigned personin-authority of the Discipline Committee. The tasks under this sanction must help the
student reflect and avoid repetition of the violated offense. The corrective reinforcement
will be lifted a day after the specified date of rendering the sanction and after the
completion of the task from a person-in-authority assigned by the Discipline Committee.
    </p>
  </div>
      
    <div id="Conference-discipline-committee">
    <h4 class="font-semibold text-2xl">Conference with the Discipline Committee</h4>
    <p>
      <br>The parents/guardians are called for a conference with the Discipline Committee,
Program Head, and/or Guidance Counselor for them to discuss the offense and the
corresponding course of action to avoid the recurrence of the offense. This is required to
be done to cases with the following sanctions: Written Apology, Written Reprimand,
Corrective Reinforcement, Suspension, Non-readmission, Exclusion, and Expulsion.
    </p>
  </div>
      
    <div id="Categories-disciplinary-penalties">
    <h4 class="font-semibold text-2xl">Categories of Disciplinary Administrative Penalties</h4>
    <p>
      <br>According to the provisions in the 2009 Manual of Regulations for Private Higher
Education (MORPHE), the four (4) categories of disciplinary administrative penalties
for serious offenses of school rules and regulations which may be applied to an
erring student are:
    </p>
  </div>
        
    <div id="suspension">
    <h4 class="font-semibold text-2xl">Suspension</h4>
    <p><br>
    In STI, this sanction has two (2) types:<br><br>
  </p>

  <ol type="a" class="list-[lower-alpha] list-inside space-y-2 ml-6">
    <li>
      <strong>Suspension from class</strong><br>
      It is a penalty that excludes the offender from regular classwork and from other privileges or activities for a definite period of time. This is to be served within a reasonable time from the issuance of the decision of the Discipline Committee.
      A student under suspension is still required to report to school from 8 am to 5 pm but is not allowed to join their classes. They are required to do the task to be determined and supervised by the assigned person-in-authority of the Discipline Committee. The tasks under this sanction must help the student reflect and avoid repetition of the violated offense. Although they will be re-admitted to school, the suspension shall be put on the student’s record. The suspension is imposed only after the parents or guardians have been informed through writing and invited to a conference with the Discipline Committee.<br>
    </li>

    <li>
      <strong>Preventive Suspension</strong><br>
      A student under investigation may be preventively suspended from entering the school premises and from attending classes, when the evidence of guilt is strong and the responsible school official is morally convinced that the continued stay of the student during the period of the investigation constitutes a distraction to the normal operations of the school or poses a risk or danger to the life of persons and property in the school. The school is allowed to impose this sanction for a period not exceeding 20% of the prescribed class days for the school term. The suspension is imposed only after the parents or guardians have been informed through writing and conference with the Discipline Committee.<br>
    </li>
  </ol>
  </div>
        
    <div id="Non-readmission">
    <h4 class="font-semibold text-2xl">Non-readmission</h4>
    <p><br>
  Non-readmission is a penalty in which the school is allowed to deny admission or
enrollment of an erring student for the school term immediately following the term
when the resolution or decision finding the student guilty of the offense charged
and imposing the penalty of non-readmission was promulgated. Unlike the penalty
of exclusion, the student is allowed to complete the current school term when the
resolution of non-readmission was promulgated. Transfer Credentials of the erring
student shall be issued upon promulgation, subject to the other provisions stated
in the MORPHE.
</p>
  </div>
          
    <div id="Exclusion">
    <h4 class="font-semibold text-2xl">Exclusion</h4>
    <p>
      <br>Exclusion is a penalty in which the school is allowed to exclude or drop the name
of the erring student from the roll of students immediately upon resolution for
exclusion was promulgated. This penalty may be imposed for acts or offenses
such as dishonesty, hazing, carrying deadly weapons, immorality, selling and/
or possession of prohibited drugs, drug dependency, drunkenness, hooliganism,
vandalism, and other offenses analogous to the foregoing. Transfer Credentials
of the erring student shall be issued upon promulgation, subject to the provisions
stated in the MORPHE. <br><br>

The school shall preserve a complete record of the proceedings for a period of one
(1) year in order to afford the Discipline Committee and Commission the opportunity
to review the case in the event the student makes and files an appeal with the
Commission on Higher Education.
    </p>
  </div>
          
    <div id="expulsion">
    <h4 class="font-semibold text-2xl">Expulsion</h4>
    <p><br>Expulsion is a penalty in which an institution declares an erring student disqualified
for admission to any public or private higher education institution in the Philippines.<br><br>

In any case, the penalty of expulsion cannot be imposed without the approval of the
Chairman of the Commission on Higher Education. This penalty may be imposed for
acts or offenses involving moral turpitude or constituting gross misconduct, which
are considered criminal pursuant to existing penal laws.<br><br>

The institution shall forward a complete record of the proceedings to the CHED
Regional Office concerned within 10 days from the termination of the investigation
of each case.</p>
  </div>
 
  <!-- Info under Expulsion -->
    <p>________________________________________________________________________________</p>
      <br>Imposition of sanctions cited in this handbook shall not in any way prejudice the filing
of cases in and the implementation of penalties prescribed by a court of law.<br><br>

Also, in cases that involve significant damage or destruction of property, the Discipline
Committee will decide whether the recipient of the sanction will replace the destroyed,
damaged, or lost property. For cases of cheating in an examination or other school
activities, a failing grade shall be given in the particular examination or activity.<br><br>

All sanctions shall go along with a one-on-one session with the School’s Guidance
Counselor or Associate.</p>
  
  <div id="offenses">
    <h4 class="font-semibold text-2xl">Offenses</h4>
    <p><br>
Offenses are behaviors or visible actions exhibited by students that go against the school
and institutional rules and regulations.
  </p>
  </div>
  
  <div id="minor-offenses">
    <h4 class="font-semibold text-2xl">Minor Offenses</h4>
     <p><br>
    These are behaviors or actions that deviate or stray from the rules of the school or from student decorum and have minimal implications or consequences to the individual, other persons, the school, or the institution.<br><br>
    The sanctions imposed for the commission of these offenses are:
  </p>

  <table class="table-auto border-collapse border border-gray-400 my-4 w-full text-left">
    <thead>
      <tr>
        <th class="border border-gray-400 px-3 py-2 font-semibold">First offense</th>
        <th class="border border-gray-400 px-3 py-2 font-semibold">Verbal Warning</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="border border-gray-400 px-3 py-2">Second offense</td>
        <td class="border border-gray-400 px-3 py-2">Written Reprimand</td>
      </tr>
      <tr>
        <td class="border border-gray-400 px-3 py-2">Third offense</td>
        <td class="border border-gray-400 px-3 py-2">
          Written Reprimand &amp; Corrective Reinforcement (minimum of three (3) school days, maximum of seven (7) school days)
        </td>
      </tr>
    </tbody>
  </table>

  <p>
    Offenses under this category include but are not limited to the following:
  </p>

  <ol type="1" class="list-decimal list-inside space-y-2 ml-6">
    <li>Non-adherence to the “STI Student Decorum”<br></li>
    <li>Discourtesy towards any member of the STI community including campus visitors<br></li>
    <li>Non-wearing of school uniform, improper use of school uniform or ID inside school premises<br></li>
    <li>Wearing inappropriate campus attire<br></li>
    <li>Losing or forgetting one’s ID three (3) times<br></li>
    <li>Disrespect to national symbols or any other similar infraction<br></li>
    <li>Irresponsible or improper use of school property<br></li>
    <li>Gambling in any form within the school premises or during official functions<br></li>
    <li>Staying or eating inside the classroom without permission from the school administrator or management<br></li>
    <li>
      Disruption of classes, school-sanctioned activities, and peace and order such as but not limited to:<br>
      <ul class="list-disc list-inside ml-6 mt-1">
        <li>Failure to turn off or put into silent mode mobile phones and other similar gadgets<br></li>
      </ul>
    </li>
    <li>Unauthorized use of social media, digital messaging, or any form of user account<br></li>
    <li>Unruly behavior (boisterous laughter, loitering, loud banter, uncontrolled giggling, and intentional misbehavior) or conduct during assemblies and the like<br></li>
    <li>Exhibiting displays of affection that negatively affect the reputation of the individuals<br></li>
    <li>Violation of classroom, laboratory, library, and other school offices procedure<br></li>
    <li>Possession of cigarettes or vapes<br></li>
    <li>Bringing of pets in the school premises<br></li>
  </ol>
  </div>
    
  <div id="major-offenses-category-a">
    <h4 class="font-semibold text-2xl">Major Offenses - Category A</h4>
    <p><br>
      These are behaviors or actions that deviate or stray from the rules of the school and/or from student decorum and have greater implications or consequences to the individual, other persons, and the school.<br><br>
      The sanctions imposed for the commission of these offenses are:
    </p>

    <table class="table-auto border-collapse border border-gray-400 my-4 w-full text-left">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2 font-semibold">Offense</th>
          <th class="border border-gray-400 px-3 py-2 font-semibold">Sanction</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-3 py-2">First offense</td>
          <td class="border border-gray-400 px-3 py-2">
            Written Reprimand &amp; Corrective Reinforcement (minimum of three (3) school days, maximum of seven (7) school days)
          </td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-3 py-2">Second offense</td>
          <td class="border border-gray-400 px-3 py-2">
            Suspension (minimum of three (3) school days, maximum of seven (7) school days)
          </td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-3 py-2">Third offense</td>
          <td class="border border-gray-400 px-3 py-2">Non-readmission</td>
        </tr>
      </tbody>
    </table>

    <p>
      Offenses under this category include but are not limited to the following:
    </p>

    <ol type="1" class="list-decimal list-inside space-y-2 ml-6">
      <li>More than three (3) commissions of any minor offense<br></li>
      <li>Lending/borrowing school ID, wearing, or using tampered ID<br></li>
      <li>Smoking or vaping inside the campus<br></li>
      <li>Entering the campus in a state of intoxication, bringing, and/or drinking liquor inside the campus<br></li>
      <li>Allowing a non-STI individual to enter the campus without official business or transaction<br></li>
      <li>
        Cheating that includes but is not limited to:<br>
        <ul class="list-disc list-inside ml-6 mt-1">
          <li>Copying and/or willfully allowing another to copy during the administration of examination and/or assessments<br></li>
          <li>Using of “Codigo” or unauthorized resources or both during examination and/or assessments<br></li>
          <li>Plagiarism<br></li>
          <li>Communicating with another student or person in any form during an examination or test without permission from the teacher or proctor<br></li>
          <li>Having somebody else take an examination or test for one’s self or prepare a required report or assignment. If both parties are students, both are liable.<br></li>
          <li>Leaking of examination questions or answer keys to another student/s in any form<br></li>
        </ul>
      </li>
    </ol>
  </div>

  <div id="major-offenses-category-b">
    <h4 class="font-semibold text-2xl">Major Offenses - Category B</h4>
    <p><br>
      These are behaviors or actions that lead to damage or destruction of property or image or both of an individual, a group, the school, or the institution.<br><br>
      The sanctions imposed for the commission of these offenses are:
    </p>

    <table class="table-auto border-collapse border border-gray-400 my-4 w-full text-left">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2 font-semibold">Offense</th>
          <th class="border border-gray-400 px-3 py-2 font-semibold">Sanction</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-3 py-2">First offense</td>
          <td class="border border-gray-400 px-3 py-2">
            Suspension (minimum of three (3) school days, maximum of seven (7) school days)
          </td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-3 py-2">Second offense</td>
          <td class="border border-gray-400 px-3 py-2">Non-readmission</td>
        </tr>
      </tbody>
    </table>

    <p>
      Offenses under this category include but are not limited to the following:
    </p>

    <ol type="1" class="list-decimal list-inside space-y-2 ml-6">
      <li>Vandalizing, damaging, or destroying of property belonging to any member of the STI community, visitors, or guests while in the school campus<br></li>
      <li>Posting or uploading of statements, photos, videos, or other graphical images disrespectful to the STI Brand, another student, faculty member, or any other individual<br></li>
      <li>Recording and uploading of photos, videos, or other graphical images that violate the data privacy of another student, faculty member, or any other individual<br></li>
      <li>Going to places of ill repute while wearing the school uniform<br></li>
      <li>Issuing a false testimony during official investigations<br></li>
      <li>Use of profane language that expresses grave insult toward any member of the STI community<br></li>
    </ol>
  </div>

  <div id="major-offenses-category-c">
    <h4 class="font-semibold text-2xl">Major Offenses - Category C</h4>
     <p><br>
      These are behaviors or actions that lead to any of the following:<br>
    </p>

    <ul class="list-disc list-inside ml-6 space-y-1">
      <li>Significant injury to the individual or other persons</li>
      <li>Endangering the safety and welfare of the individual and other persons</li>
      <li>Degrading the integrity of the person, school, or the institution</li>
    </ul>

    <p><br>The sanctions imposed for the commission of these offenses are:</p>

    <table class="table-auto border-collapse border border-gray-400 my-4 w-full text-left">
      <thead>
        <tr>
          <th class="border border-gray-400 px-3 py-2 font-semibold">Offense</th>
          <th class="border border-gray-400 px-3 py-2 font-semibold">Sanction</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="border border-gray-400 px-3 py-2">First offense</td>
          <td class="border border-gray-400 px-3 py-2">
            Suspension (minimum of seven (7) school days, maximum of ten (10) school days)
          </td>
        </tr>
        <tr>
          <td class="border border-gray-400 px-3 py-2">Second offense</td>
          <td class="border border-gray-400 px-3 py-2">Non-readmission</td>
        </tr>
      </tbody>
    </table>

    <p>
      Offenses under this category include but are not limited to the following: <br><br>
    </p>

    <ol type="1" class="list-decimal list-inside space-y-2 ml-6">
      <li>“Hacking” attacks on the computer system of the school or other institutions or both<br></li>
      <li>Stealing, tampering, or forgery of records and receipts<br></li>
      <li>Theft or robbery of school property or those belonging to school officials, teachers, personnel, other students, any member of the STI community, visitors, and guests<br></li>
      <li>
        Unauthorized copying, distribution, modification, or exhibition — in whole or in part — of eLMS materials or other learning materials provided by STI such as but not limited to videos, PowerPoint presentations, handouts, activity worksheets, and answer keys. This will include:<br>
        <ul class="list-disc list-inside ml-6 mt-1">
          <li>Use of the materials for any commercial purpose or for any public display (commercial or non-commercial)<br></li>
          <li>Attempt to decompile or reverse engineer any software contained on the eLMS<br></li>
          <li>Remove any copyright or other proprietary notations from the materials<br></li>
          <li>Transfer the materials to another person or “mirror” the materials on any other server or sites<br></li>
        </ul>
      </li>
      <li>Embezzlement and malversation of school or organization funds or property<br></li>
      <li>Disruption of academic functions or school activities through illegal assemblies, demonstrations, boycotts, pickets, or mass actions or related activities, with the intent to create public disorder or disturbance<br></li>
      <li>Any act of immorality<br></li>
      <li>Any act of bullying (such as but not limited to physical, cyber, and verbal)<br></li>
      <li>Participation in brawls or infliction of physical injuries within or outside school premises, whether in school uniform or not<br></li>
      <li>Physical assault upon or threats to any member within or outside the school premises, whether in school uniform or not<br></li>
      <li>Use of prohibited drugs or chemicals in any form within and outside the school premises, whether in uniform or not<br></li>
      <li>Giving false or malicious fire alarms and bomb threats<br></li>
      <li>Use of fire protective or firefighting equipment of the school other than for firefighting except in other emergencies where their use is justified<br></li>
    </ol>
  </div>

  <div id="major-offenses-category-d">
    <h4 class="font-semibold text-2xl">Major Offenses - Category D</h4>
     <p><br>
      These are behaviors or actions that are in direct violation of the Philippine Laws.
    </p>
    <p><br>
      The sanction imposed for the commission of these offenses is either <strong>Exclusion/Expulsion.</strong>
    </p>

    <p><br>
      Offenses under this category include but are not limited to the following:<br><br>
    </p>

    <ol type="1" class="list-decimal list-inside space-y-2 ml-6">
      <li>
        Possession or sale of prohibited drugs or chemicals in any form, or any illegal drug paraphernalia within and outside the school premises whether in uniform or not
      </li>
      <li>
        Continued use and being found to be “confirmed positive” of using prohibited drugs or chemicals for the second time, even after undergoing an intervention
      </li>
      <li>
        Carrying or possession of firearms, deadly weapons, and explosives within and outside the school premises, whether in uniform or not
      </li>
      <li>
        Membership or affiliation in organizations, such as but not limited to fraternities and sororities, that employ or advocate illegal rites or ceremonies, which include hazing and initiation
      </li>
      <li>
        Participation in illegal rites, ceremonies, and ordeals, which includes hazing and initiation
      </li>
      <li>
        Commission of crime involving moral turpitude (such as but not limited to rape, forgery, estafa, acts of lasciviousness, moral depravity, murder, and homicide)
      </li>
      <li>
        Commission of acts constituting sexual harassment as defined in the Student Manual and Republic Act 7877, otherwise known as the “Anti-Sexual Harassment Act of 1995”
      </li>
      <li>
        Acts of subversion, sedition, or insurgency
      </li>
    </ol>
  </div>
    
  <div id="offenses">
    <h4 class="font-semibold text-2xl">Disciplinary Cases or Offenses Not Written in the Student Handbook</h4>
    <p><br>Disciplinary cases or offenses not written in the Student Handbook are subject to the review
of the Discipline Committee and school administration in the interest of upholding the ideal
learning environment and of the STI Community.</p>
  </div>
  
</section>

<!-- ================= APPENDICES ================= -->
<section id="appendices" class="space-y-12 text-justify text-lg mt-16">
  <h3 class="text-3xl font-semibold mb-6">APPENDICES</h3>

  <!-- Appendix A -->
  <div id="appendix-a">
    <h4 class="text-2xl font-semibold mb-4">Appendix A</h4>
    <div class="text-center">
      <h4 class="text-xl font-bold mb-6">The STIer’s Creed</h4>
      <p class="leading-relaxed">
        I am an STIer, I am here to learn.<br>
        I thirst for knowledge and skills that will<br>
        make me a leader of tomorrow.<br><br>

        I am an STIer, I keep an open mind.<br>
        I challenge every knowledge I seek<br>
        and understand.<br><br>

        I am an STIer, I embrace change.<br>
        I continuously reinvent myself.<br><br>

        I am an STIer, I am a person of character.<br>
        I speak, I act, and I live for the common good.<br><br>

        I am an STIer, I am determined.<br>
        I accept the challenge to become the best<br>
        that I can be.<br><br>

        I am an STIer, a proud STIer!
      </p>
    </div>
  </div>

  <!-- Appendix B -->
  <div id="appendix-b">
    <h4 class="text-2xl font-semibold mb-4">Appendix B</h4>
    <div class="text-center">
      <h4 class="text-xl font-bold mb-6">STI Hymn</h4>
      <p class="leading-relaxed">
        Aim high with STI<br>
        The future is here today<br>
        Fly high with STI<br>
        Be the best that you can be.<br><br>

        Onward to tomorrow<br>
        With dignity and pride<br>
        A vision of excellence<br>
        Our resounding battle cry.<br><br>

        Aim high with STI<br>
        The future is here today<br>
        Fly high with STI<br>
        Be the best that you can be.
      </p>
    </div>
  </div>

  <!-- Appendix C -->
  <div id="appendix-c">
    <h4 class="text-2xl font-semibold mb-4">Appendix C</h4>
    <div class="text-center">
      <h4 class="text-xl font-bold mb-6">Student Commitment Form</h4>
    </div>

    <p class="leading-relaxed">
      I, the undersigned, have received, read, and understood everything stated in the Student
      Handbook of STI. I hereby affix my signature as confirmation that I will faithfully abide
      and be guided by all the policies and procedures as clearly specified in the Student Handbook.
      I also commit to faithfully abide and be guided by policies and procedures issued after
      the release of this handbook. Non-compliance on my part with any rule or regulation shall
      constitute sufficient grounds for disciplinary action, including but not limited to suspension
      up to expulsion from STI depending on the gravity of my offense.
    </p>

    <div class="mt-8 space-y-6">
      <p>Signed on this Date: ____________________________</p>

      <p>
        By: ____________________________________________<br>
        <span class="text-sm italic">PRINTED NAME AND SIGNATURE OF STUDENT</span>
      </p>

      <p>
        By: ____________________________________________<br>
        <span class="text-sm italic">PRINTED NAME AND SIGNATURE OF PARENT/GUARDIAN</span>
      </p>

      <p>Address: ____________________________________________</p>
      <p>Telephone number: ____________________________________________</p>
      <p>Program: ____________________________________________</p>
    </div>
  </div>
</section>

    </div>
    </div>
  </div>





<!-- Table of Contents (Right Sidebar) -->
<aside>
  <div
    id="toc-sidebar"
    class="hidden xl:flex flex-col w-95 sticky top-28 max-h-[90vh] overflow-y-auto 
           bg-gray-100 dark:bg-[#111827] rounded-lg border border-gray-300 dark:border-slate-700
           p-3 mr-6 text-lg custom-scrollbar leading-relaxed">
    <h3 class="text-gray-700 dark:text-gray-300 font-bold mb-5 uppercase tracking-wide text-2xl">
      On this page
    </h3>

    <ul class="space-y-5">
      <!-- GENERAL INFORMATION -->
      <li>
        <a href="#general-info" class="text-gray-800 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold block text-xl pl-4">
          General Information
        </a>
        <ul class="pl-6 mt-2 space-y-2">
          <li><a href="#sti-history" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">STI History</a></li>
          <li><a href="#sti-vision" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">STI Vision</a></li>
          <li><a href="#sti-mission" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">STI Mission</a></li>
          <li><a href="#sti-seal" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">STI Academic Seal</a></li>
          <li><a href="#sti-philosophy" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">STI Educational Philosophy</a></li>
          <li><a href="#sti-way" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">STI Way of Educating</a></li>
          <li><a href="#character" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Character</a></li>
          <li><a href="#critical-thinker" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Critical Thinker</a></li>
          <li><a href="#communicator" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Communicator</a></li>
          <li><a href="#change-adept" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Change-Adept</a></li>
          <li><a href="#educational-goal" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Educational Goal</a></li>
          <li><a href="#sti-network" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">STI Educational Network System</a></li>
          <li><a href="#colleges" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">The Colleges</a></li>
          <li><a href="#education-centers" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">The Education Centers</a></li>
          <li><a href="#senior-high" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">The Senior High Schools</a></li>
          <li><a href="#junior-high" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">The Junior High Schools</a></li>
        </ul>
      </li>

<!-- ACADEMIC POLICIES -->
<li>
  <a href="#academic-policies" class="text-gray-800 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold block text-xl mt-3 pl-4">
    Academic Policies & Procedures
  </a>
  <ul class="pl-6 mt-2 space-y-2">
    <li><a href="#school-student-relationship" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">School-Student Relationship</a></li>
    <li><a href="#admission-policy" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Admission Policy and Requirements</a></li>
    <li><a href="#incoming-freshmen" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Incoming Freshmen</a></li>
    <li><a href="#transferees" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Transferees</a></li>
    <li><a href="#als" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">ALS A&E Passers</a></li>
    <li><a href="#foreign-students" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Foreign Students</a></li>
    <li><a href="#special-students" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Special Students</a></li>
    <li><a href="#disqualification" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Disqualification</a></li>
    <li><a href="#residency" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Residency</a></li>
    <li><a href="#minimum-residency" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Minimum Residency</a></li>
    <li><a href="#maximum-residency" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Maximum Residency</a></li>
    <li><a href="#leave-of-absence" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Leave of Absence (LOA)</a></li>
    <li><a href="#extension-of-leave" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Extension of Leave</a></li>
    <li><a href="#return-to-school" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Return to School</a></li>
    <li><a href="#awol-status" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">AWOL Status</a></li>
    <li><a href="#cross-enrollment" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Cross Enrollment</a></li>
    <li><a href="#conditions-cross-enrollment" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Conditions for STI Students Cross Enrolling to Another School</a></li>
    <li><a href="#requirements-cross-enrollment" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Requirements for Students Cross Enrolling to Other School</a></li>
    <li><a href="#school-year" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">School Year</a></li>
    <li><a href="#student-classification" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Classification</a></li>
    <li><a href="#study-load" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Study Load</a></li>
    <li><a href="#term-load" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Term Load</a></li>
    <li><a href="#midyear-load" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Midyear Load</a></li>
    <li><a href="#underload" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Underload</a></li>
    <li><a href="#overload" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Overload</a></li>
    <li><a href="#conditions-overload" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Conditions for Student Overload Units</a></li>
    <li><a href="#standard-examinations" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Standard Periodical Examinations</a></li>
    <li><a href="#periodical-examinations" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Periodical Examinations</a></li>
    <li><a href="#schedule" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Schedule</a></li>
    <li><a href="#missed-examinations" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Missed Examinations</a></li>
    <li><a href="#special-examinations" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Special Examinations</a></li>
    <li><a href="#grading-earned-credits" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Grading and Earned Credits</a></li>
    <li><a href="#grading-system" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Grading System</a></li>
    <li><a href="#course-grade" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Course Grade</a></li>
    <li><a href="#periodical-score" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Periodical Score</a></li>
    <li><a href="#release-of-grades" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Release of Grades</a></li>
    <li><a href="#gwa" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">General Weighted Average</a></li>
    <li><a href="#student-works" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Works</a></li>
    <li><a href="#attendance" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Attendance</a></li>
    <li><a href="#absences" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Absences</a></li>
    <li><a href="#waiting-period" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Waiting Period</a></li>
    <li><a href="#suspension-of-classes" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Suspension of Classes</a></li>
    <li><a href="#course-sequence" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Course Sequence</a></li>
    <li><a href="#prerequisite" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Prerequisite</a></li>
    <li><a href="#corequisite" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Corequisite</a></li>
    <li><a href="#petitioned-classes" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Petitioned Classes</a></li>
    <li><a href="#change-of-courses-schedules" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Change of Courses or Schedules</a></li>
    <li><a href="#dropping-of-courses" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Dropping of Courses</a></li>
    <li><a href="#shifting-of-academic-program" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Shifting of Academic Program</a></li>
    <li><a href="#fees-payments" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Fees & Payments</a></li>
    <li><a href="#payment-schemes" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Payment Schemes</a></li>
    <li><a href="#installment" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Installment</a></li>
    <li><a href="#refund-of-payment" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Refund of Payment</a></li>
    <li><a href="#special-admission-foreign-students" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Special Admission Fee for Foreign Students</a></li>
    <li><a href="#financial-obligations" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Financial Obligations</a></li>
    <li><a href="#honors" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Academic Honors</a></li>
    <li><a href="#deans-presidents-honors" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Dean's and President's Honors List</a></li>
    <li><a href="#scholarships-financial-aid" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Scholarships and Financial Aid</a></li>
    <li><a href="#student-assistantship-program" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Assistantship Program</a></li>
    <li><a href="#ojt-practicum" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">On-the-job Training (OJT)/Practicum</a></li>
    <li><a href="#academic-standing" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Academic Standing</a></li>
    <li><a href="#academic-delinquency" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Academic Delinquency</a></li>
    <li><a href="#warning" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Warning</a></li>
    <li><a href="#academic-probation-retention" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Academic Probation and Retention</a></li>
    <li><a href="#dismissal" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Dismissal</a></li>
    <li><a href="#matrix-academic-delinquency" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Matrix of Academic Delinquency Status</a></li>
    <li><a href="#remediation-programs" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Remediation Programs</a></li>
    <li><a href="#remedial-classes" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Remedial Classes</a></li>
    <li><a href="#peer-tutoring" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Peer Tutoring</a></li>
    <li><a href="#faculty-consultation" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Faculty Consultation</a></li>
    <li><a href="#graduation" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Graduation</a></li>
    <li><a href="#requirements-graduation" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Requirements for Graduation</a></li>
    <li><a href="#declaration-intent-graduate" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Declaration of Intent to Graduate</a></li>
    <li><a href="#list-candidates-graduation" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">List of Candidates for Graduation</a></li>
    <li><a href="#graduation-honors" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Graduation Honors</a></li>
    <li><a href="#classification-honors" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Classification of Honors</a></li>
    <li><a href="#eligibility-honors" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Eligibility for Honors</a></li>
    <li><a href="#graduation-dress-code" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Graduation Dress Code</a></li>
    <li><a href="#male-graduation-attire" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Male</a></li>
    <li><a href="#female-graduation-attire" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Female</a></li>
    <li><a href="#special-graduation-awards" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Special Graduation Awards</a></li>
    <li><a href="#sti-most-outstanding-thesis-award" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">STI Most Outstanding Thesis Award</a></li>
    <li><a href="#graduation-credentials" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Graduation Credentials</a></li>
    <li><a href="#cpdt" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Centralized Printing of Diploma and Transcript of Records</a></li>
  </ul>
</li>


      <!-- STUDENT SERVICES -->
      <li>
        <a href="#student-services" class="text-gray-800 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold block text-xl mt-3 pl-4">
          Student Services
        </a>
        <ul class="pl-6 mt-2 space-y-2">
        <li><a href="#guidance" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Guidance & Counseling</a></li>
        <li><a href="#student-records" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Records</a></li>
        <li><a href="#ict-services" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">ICT Services</a></li>
        <li><a href="#library-services" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Library</a></li>
        <li><a href="#sports" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Sports Development</a></li>
        <li><a href="#health-services" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Health Services</a></li>
        <li><a href="#special-needs" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Special Needs & PWD</a></li>
        <li><a href="#student-affairs" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Affairs</a></li>
        <li><a href="#offcampus-activities" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Off-Campus Activities</a></li>
        <li><a href="#student-orgs" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Organizations</a></li>
        <li><a href="#student-publications" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Publications</a></li>
        <li><a href="#placement-assistance" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Placement Assistance Services</a></li>
        <li><a href="#eps" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Employment Preparation Seminars (EPS)</a></li>
        <li><a href="#mock-interview" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Mock Interview</a></li>
        <li><a href="#video-resume" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Video Resume</a></li>
        <li><a href="#job-fairs" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Job Fairs / Virtual Recruitment</a></li>
        <li><a href="#virtual-career-fair" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Virtual Career Fair (VCF)</a></li>
        <li><a href="#recruitment-day" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Recruitment Day</a></li>
        <li><a href="#icare-system" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">I-CARE System</a></li>
        <li><a href="#alumni-services" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Alumni Services</a></li>
        <li><a href="#auxiliary-services" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Auxiliary Services</a></li>
        <li><a href="#security-safety" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Security and Safety Services</a></li>
        <li><a href="#maintenance" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Maintenance</a></li>
        </ul>
      </li>

<!-- STUDENT BEHAVIOR -->
<li>
  <a href="#student-behavior" class="text-gray-800 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold block text-xl mt-3 pl-4">
    Student Behavior & Discipline
  </a>

  <ul class="pl-6 mt-2 space-y-2">
    <li><a href="#student-appearance" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Appearance</a></li>
    <li><a href="#school-id" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">School Identification Card</a></li>
    <li><a href="#school-id-replacement" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Procedure for ID Card Replacement</a></li>
    <li><a href="#student-uniform" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Uniform</a></li>
    <li><a href="#wash-day" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Wash Day</a></li>
    <li><a href="#grooming-haircut" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Grooming and Haircut</a></li>
    <li><a href="#student-decorum" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Decorum</a></li>
    <li><a href="#anti-bullying" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Anti-Bullying & Anti-Cyberbullying Law Policy</a></li>
    <li><a href="#anti-hazing" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Anti-Hazing Law Policy</a></li>
    <li><a href="#anti-harassment" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Anti-Sexual Harassment Policy</a></li>
    <li><a href="#gender-development" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Gender & Development Policy</a></li>
    <li><a href="#prohibited-items" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Prohibited Items & Substances</a></li>
    <li><a href="#random-drug-testing" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Random Drug Testing</a></li>
    <li><a href="#electronic-gadget-rule" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Electronic Gadget Rule</a></li>
    <li><a href="#social-media-policy" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Social Media Policy</a></li>
    <li><a href="#data-privacy-policy" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Data Privacy Policy</a></li>
    <li><a href="#student-discipline" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Discipline</a></li>
    <li><a href="#discipline-committee" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Discipline Committee</a></li>
    <li><a href="#initial-settlement" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Initial Settlement</a></li>
    <li><a href="#disciplinary-sanctions" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Implementation of Disciplinary Sanctions</a></li>
    <li><a href="#complaints" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Student Complaints</a></li>
    <li><a href="#disciplinary-cases-procedure" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Procedure for Disciplinary Cases</a></li>
    <li><a href="#guidance-discipline-procedure" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Guidance & Counseling Procedure</a></li>
    <li><a href="#disciplinary-measures" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Disciplinary Measures</a></li>
    <li><a href="#corrective-actions" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Corrective Actions to Minor and Major Offenses</a></li>
    <li><a href="#verbal-oral-warning" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Verbal/Oral Warning</a></li>
    <li><a href="#written-apology" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Written Apology</a></li>
    <li><a href="#written-reprimand" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Written Reprimand</a></li>
    <li><a href="#corrective-reinforcement" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Corrective Reinforcement</a></li>
    <li><a href="#Conference-discipline-committee" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Conference with the Discipline Committee</a></li>
    <li><a href="#Categories-disciplinary-penalties" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Categories of Disciplinary Administrative Penalties</a></li>
    <li><a href="#suspension" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Suspension</a></li>
    <li><a href="#Non-readmission" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Non-readmission</a></li>
    <li><a href="#Exclusion" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Exclusion</a></li>
    <li><a href="#expulsion" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Expulsion</a></li>
    <li><a href="#offenses" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Offenses</a></li>
    <li><a href="#minor-offenses" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Minor Offenses</a></li>
    <li><a href="#major-offenses-category-a" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Major Offenses - Category A</a></li>
    <li><a href="#major-offenses-category-b" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Major Offenses - Category B</a></li>
    <li><a href="#major-offenses-category-c" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Major Offenses - Category C</a></li>
    <li><a href="#major-offenses-category-d" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Major Offenses - Category D</a></li>
    <li><a href="#offenses" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Unlisted Disciplinary Cases</a></li>
  </ul>
</li>


      <!-- APPENDICES -->
      <li>
        <a href="#appendices" class="text-gray-800 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold block text-xl mt-3 pl-4">
          Appendices
        </a>
        <ul class="pl-6 mt-2 space-y-2">
          <li><a href="#appendix-a" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Appendix A - The STIer’s Creed</a></li>
          <li><a href="#appendix-b" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Appendix B - STI Hymn</a></li>
          <li><a href="#appendix-c" class="text-gray-700 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 text-lg">Appendix C - Student Commitment Form</a></li>
        </ul>
      </li>
    </ul>
  </div>
</aside>
        </main>
</div>
</div>


